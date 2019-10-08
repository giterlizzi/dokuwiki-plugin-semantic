<?php
/**
 * Semantic plugin: Add Schema.org News Article using JSON-LD
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @copyright  (C) 2015-2019, Giuseppe Di Terlizzi
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class helper_plugin_semantic extends DokuWiki_Plugin
{

    private $meta = array();
    private $page = null;

    /**
     * Get Schema.org WebSite
     *
     * @return array
     */
    public function getWebSite()
    {

        global $conf;

        $json_ld = array(
            '@context'        => 'http://schema.org',
            '@type'           => 'WebSite',
            'url'             => DOKU_URL,
            'name'            => $conf['title'],
            'potentialAction' => array(
                '@type'       => 'SearchAction',
                'target'      => DOKU_URL . DOKU_SCRIPT . '?do=search&amp;id={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ),
        );

        return $json_ld;

    }

    /**
     * Get the metadata of the page
     *
     * @param string $page ID
     *
     * @return string
     */
    public function getMetadata($page)
    {

        global $INFO;
        global $ID;
        global $license;
        global $auth;
        global $conf;

        $this->page = cleanID($page);

        $auth_check = auth_quickaclcheck($this->page);

        if ((bool) preg_match('/' . trim($this->getConf('excludedPages')) . '/', $this->page)) {
            return false;
        }

        if (!$auth_check) {
            return false;
        }

        $this->meta = p_get_metadata($this->page);

        if (isset($this->meta['plugin']['semantic']['enabled']) && !$this->meta['plugin']['semantic']['enabled']) {
            return false;
        }

        if (!isset($this->meta['date']) || $this->meta['date'] == '') {
            return false;
        }

        return $this->meta;

    }

    /**
     * Get Schema.Org page type
     *
     * @return string
     */
    public function getSchemaOrgType()
    {

        return ((isset($this->meta['plugin']['semantic']['schema.org']['type']))
            ? $this->meta['plugin']['semantic']['schema.org']['type']
            : $this->getConf('defaultSchemaOrgType'));
    }

    /**
     * Get the first image in page
     *
     * @return string
     */
    public function getFirstImage()
    {
        return ((@$this->meta['relation']['firstimage']) ? $this->meta['relation']['firstimage'] : null);
    }

    /**
     * Get the URL of the first image in page
     *
     * @return string
     */
    public function getFirstImageURL()
    {
        return ($this->getFirstImage() ? ml($this->getFirstImage(), '', true, '&amp;', true) : null);
    }

    /**
     * Get page description
     *
     * @return string
     */
    public function getDescription()
    {
        return (@$this->meta['description']['abstract'] ? $this->meta['description']['abstract'] : $this->getTitle());
    }

    /**
     * Get author name
     *
     * @return string
     */
    public function getAuthor()
    {
        return ($this->meta['creator'] ? $this->meta['creator'] : null);
    }

    /**
     * Get author ID
     *
     * @return string
     */
    public function getAuthorID()
    {
        return ($this->meta['user'] ? $this->meta['user'] : null);
    }

    /**
     * Get the page title
     *
     * @return string
     */
    public function getTitle()
    {
        return (@$this->meta['title'] ? $this->meta['title'] : null);
    }

    /**
     * Get the create date of page
     *
     * @return int
     */
    public function getCreatedDate()
    {
        return ((@$this->meta['date']['created']) ? $this->meta['date']['created'] : -1);
    }

    /**
     * Get the modified date of page
     *
     * @return int
     */
    public function getModifiedDate()
    {
        return ((@$this->meta['date']['modified']) ? $this->meta['date']['modified'] : -1);
    }

    /**
     * Get DokuWiki license
     *
     * @return string
     */
    public function getLicense()
    {
        global $license;
        global $conf;
        return @$license[$conf['license']];
    }

    /**
     * Return JSON-LD structured data in according of selected Schema.org type
     *
     * @return array
     */
    public function getStructuredData()
    {

        global $auth;
        global $conf;

        if (!count($this->meta)) {
            return false;
        }

        $license        = $this->getLicense();
        $type           = $this->getSchemaOrgType();
        $user_data      = ($this->getConf('hideMail') ? array('mail' => null) : $auth->getUserData($this->getAuthorID()));
        $license_url    = $license['url'];
        $page_url       = wl($this->page, '', true);
        $description    = str_replace("\n", ' ', $this->getDescription());
        $created        = date(DATE_W3C, $this->getCreatedDate());
        $modified       = date(DATE_W3C, $this->getModifiedDate());
        $title          = (isset($this->meta['title']) ? $this->meta['title'] : $this->page);
        $wiki_logo_info = array();
        $wiki_logo      = tpl_getMediaFile(array(':wiki:logo.png', ':logo.png', 'images/logo.png'), true, $wiki_logo_info);

        $json_ld = array(
            '@context'         => 'http://schema.org',
            '@type'            => $type,
            'headline'         => $title,
            'name'             => $title,
            'datePublished'    => $created,
            'dateCreated'      => $created,
            'dateModified'     => $modified,
            'description'      => $description,
            'license'          => $license_url,
            'url'              => $page_url,

            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id'   => $page_url,
            ),

            'publisher'        => array(
                '@type' => 'Organization',
                'name'  => $conf['title'],
                'logo'  => array(
                    '@type' => 'ImageObject',
                    'url'   => $wiki_logo,
                ),
            ),

        );

        if ($image_url = $this->getFirstImageURL()) {

            $image_info    = array();
            $article_image = tpl_getMediaFile(array(':' . $this->getFirstImage()), true, $image_info);

            $json_ld['image'] = array(
                '@type'  => 'ImageObject',
                'url'    => $image_url,
                'width'  => $image_info[0],
                'height' => $image_info[1],
            );

        } else {

            // Fallback
            //$json_ld['image'] = $json_ld['publisher']['logo'];

        }

        if ($author = $this->getAuthor()) {

            $json_ld['author'] = array(
                '@context' => 'http://schema.org',
                '@type'    => 'Person',
                'name'     => $author,
                'email'    => $user_data['mail'],
            );

            if (isset($this->meta['contributor'])) {
                foreach ($this->meta['contributor'] as $uid => $fullname) {

                    $contributor_data = ($this->getConf('hideMail') ? array('mail' => null) : $auth->getUserData($uid));

                    $json_ld['contributor'][] = array(
                        '@context' => 'http://schema.org',
                        '@type'    => 'Person',
                        'name'     => $fullname,
                        'email'    => $contributor_data['mail'],
                    );
                }
            }

        }

        return $json_ld;

    }

    public function getJsonLD()
    {

        $json_ld = array();

        if ($structured_data = $this->getStructuredData()) {
            $json_ld[] = $structured_data;
        }

        if ($backlinks = $this->getBacklinks()) {
            $json_ld[] = $backlinks;
        }

        return $json_ld;

    }

    public function getBacklinks()
    {

        if (!$backlinks = ft_backlinks($this->page)) {
            return false;
        }

        $json_ld_webpage = array(
            '@context' => 'http://schema.org',
            '@type'    => 'WebPage',
        );

        foreach ($backlinks as $pageid) {
            $json_ld_webpage['relatedLink'][] = wl($pageid, '', true);
        }

        if (isset($json_ld_webpage['relatedLink'])) {
            return $json_ld_webpage;
        }

    }

    public function getDublinCore()
    {

        global $conf;

        if (!$this->meta) {
            return array();
        }

        $license      = $this->getLicense();
        $contributors = array();

        if (isset($this->meta['contributor']) && is_array($this->meta['contributor'])) {
            foreach ($this->meta['contributor'] as $uid => $fullname) {
                $contributors[] = $fullname;
            }
        }

        $dublin_core = array(
            'DC.Title'        => $this->getTitle(),
            'DC.Description'  => str_replace("\n", ' ', $this->getDescription()),
            'DC.Publisher'    => $this->getAuthor(),
            'DC.Contributors' => implode(', ', $contributors),
            'DC.Rights'       => $license['name'],
            'DC.Language'     => $conf['lang'],
            'DC.Created'      => date(DATE_W3C, $this->getCreatedDate()),
            'DC.Modified'     => date(DATE_W3C, $this->getModifiedDate()),
            'DC.Date'         => date(DATE_W3C, $this->getCreatedDate()),
            'DC.Identifier'   => "urn:" . $this->page,
        );

        return $dublin_core;

    }

    public function getOpenGraph()
    {

        global $conf;

        if (!$this->meta) {
            return array();
        }

        $locale = $conf['lang'];

        if ($locale == 'en') {
            $locale = 'en_GB';
        } else {
            $locale .= '_' . strtoupper($locale);
        }

        $open_graph = array(

            'og:title'               => $this->getTitle(),
            'og:description'         => str_replace("\n", ' ', $this->getDescription()),
            'og:url'                 => wl($this->page, '', true),
            'og:type'                => 'article',
            'og:image'               => $this->getFirstImageURL(),
            'og:locale'              => $locale,
            'og:site_name'           => $conf['title'],

            'article:published_time' => date(DATE_W3C, $this->getCreatedDate()),
            'article:modified_time'  => date(DATE_W3C, $this->getModifiedDate()),
            'article:section'        => date(DATE_W3C, $this->getModifiedDate()),
            'article:author'         => $this->getAuthor(),

        );

        return $open_graph;

    }

}
