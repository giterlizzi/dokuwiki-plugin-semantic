<?php
/**
 * Semantic Action Plugin
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi>
 * @copyright  (C) 2015, Giuseppe Di Terlizzi
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class Semantic Action Plugin
 *
 * Add semantic data to DokuWiki
 */
class action_plugin_semantic extends DokuWiki_Action_Plugin {
 
    /**
     * Register events
     *
     * @param  Doku_Event_Handler  $controller
     */
    public function register(Doku_Event_Handler $controller) {

        if ($this->getConf('useJSONLD')) {
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'json_ld');
        }

        if ($this->getConf('useDescription')) {
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_description');
        }

        if ($this->getConf('useMetaAuthor')) {
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_author');
        }

    }
 
    /**
     * JSON-LD Event handler
     *
     * @param  Doku_Event  &$event
     */
    public function json_ld(Doku_Event &$event, $param) {

        global $INFO;
        global $ID;

        if ((bool) preg_match_all('/'.$this->getConf('excludedPages').'/', $ID)) {
            return false;
        }

        if ($INFO['perm'] > 0) {

            global $license;
            global $auth;
            global $conf;

            $meta = $INFO['meta'];

            if (isset($meta['semantic']['enabled']) && ! $meta['semantic']['enabled']) {
                return false;
            }

            if (isset($meta['date'])) {

                $type        = ((isset($meta['semantic']['schema.org']['type']))
                                ? $meta['semantic']['schema.org']['type']
                                : $this->getConf('defaultSchemaOrgType'));
                $user_data   = $auth->getUserData($meta['user']);
                $license_url = $license[$conf['license']]['url'];
                $page_url    = wl($ID, '', true);
                $image_url   = (($meta['relation']['firstimage']) ? ml($meta['relation']['firstimage'], '', true, '&amp;', true) : null);
                $description = trim(ltrim($meta['description']['abstract'], $meta['title']));
                $created     = date(DATE_W3C, $meta['date']['created']);
                $modified    = date(DATE_W3C, $meta['date']['modified']);

                $json_ld = array(
                    '@context'      => 'http://schema.org',
                    '@type'         => $type,
                    'headline'      => $meta['title'],
                    'name'          => $meta['title'],
                    'image'         => array($image_url),
                    'datePublished' => $created,
                    'dateCreated'   => $created,
                    'dateModified'  => $modified,
                    'description'   => $description,
                    'license'       => $license_url,
                    'url'           => $page_url,
                );

                if (isset($meta['creator']) && $meta['creator'] !== '') {
                    $json_ld['creator'] = array(
                        '@context' => 'http://schema.org',
                        '@type'    => 'Person',
                        'name'     => $meta['creator'],
                        'email'    => $user_data['mail']
                    );

                    foreach ($meta['contributor'] as $uid => $fullname) {
                        $contributor_data = $auth->getUserData($uid);
                        $json_ld['contributor'][] = array(
                            '@context' => 'http://schema.org',
                            '@type'    => 'Person',
                            'name'     => $fullname,
                            'email'    => $contributor_data['mail']
                        );
                    }

                }

                if (isset($meta['relation']['references'])) {

                    $json_ld_webpage = array(
                        '@context' => 'http://schema.org',
                        '@type'    => 'WebPage'
                    );

                    foreach ($meta['relation']['references'] as $page => $status) {
                        if ($status) {
                            $json_ld_webpage['relatedLink'][] = wl($page, '', true);
                        }
                    }

                    $event->data["script"][] = array (
                        "type"  => "application/ld+json",
                        "_data" => json_encode($json_ld_webpage),
                    );

                }

                $event->data["script"][] = array (
                    "type"  => "application/ld+json",
                    "_data" => json_encode($json_ld),
                );

            }
        }

    }


    public function meta_description(Doku_Event &$event, $params) {

        global $INFO;
        global $ID;

        if ((bool) preg_match_all('/'.$this->getConf('excludedPages').'/', $ID)) {
            return false;
        }

        if ($INFO['perm'] > 0) {

            $meta = $INFO['meta'];

            if ($meta['date'] && $meta['semantic']['enabled']) {
    
                $description = str_replace("\n", ' ', trim(ltrim($meta['description']['abstract'], $meta['title'])));
        
                $event->data['meta'][] = array(
                    'name'    => 'description',
                    'content' => $description,
                );

            }

        }

    }


    public function meta_author(Doku_Event &$event, $params) {

        global $INFO;
        global $ID;

        if ((bool) preg_match_all('/'.$this->getConf('excludedPages').'/', $ID)) {
            return false;
        }

        if ($this->getConf('useMetaAuthor') && $INFO['perm'] > 0) {
    
            if ($meta['date'] && $meta['semantic']['enabled']) {

                $meta = $INFO['meta'];
        
                $event->data['meta'][] = array(
                    'name'    => 'author',
                    'content' => $meta['creator'],
                );

            }

        }

    }

}

