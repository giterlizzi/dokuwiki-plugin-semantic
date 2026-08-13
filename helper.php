<?php

/**
 * Semantic plugin: Add Schema.org News Article using JSON-LD
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @copyright  (C) 2015-2026, Giuseppe Di Terlizzi
 */

class helper_plugin_semantic extends DokuWiki_Plugin
{
    private $meta = [];
    private $page = null;

    /**
     * Get Schema.org WebSite
     *
     * @return array
     */
    public function getWebSite()
    {
        global $conf;

        $json_ld = [
            '@type'           => 'WebSite',
            'url'             => DOKU_URL,
            'name'            => $conf['title'],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => DOKU_URL . DOKU_SCRIPT . '?do=search&amp;id={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];

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
        return (isset($this->meta['relation']['firstimage']) ? $this->meta['relation']['firstimage'] : null);
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
        return (isset($this->meta['description']['abstract']) ? $this->meta['description']['abstract'] : $this->getTitle());
    }

    /**
     * Get author name
     *
     * @return string
     */
    public function getAuthor()
    {
        return array_key_exists('creator', $this->meta) ? $this->meta['creator'] : null;
    }

    /**
     * Get author ID
     *
     * @return string
     */
    public function getAuthorID()
    {
        return (isset($this->meta['user']) ? $this->meta['user'] : null);
    }

    /**
     * Get the page title
     *
     * @return string
     */
    public function getTitle()
    {
        return (isset($this->meta['title']) ? $this->meta['title'] : null);
    }

    /**
     * Get page tags
     * 
     * @return array
     */
    public function getTags()
    {
        return (isset($this->meta['subject']) ? $this->meta['subject'] : []);
    }

    /**
     * Get the create date of page
     *
     * @return int
     */
    public function getCreatedDate()
    {
        return (isset($this->meta['date']['created']) ? $this->meta['date']['created'] : -1);
    }

    /**
     * Get the modified date of page
     *
     * @return int
     */
    public function getModifiedDate()
    {
        return (isset($this->meta['date']['modified']) ? $this->meta['date']['modified'] : -1);
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

        return (isset($license[$conf['license']]) ? $license[$conf['license']] : null);
    }

    /**
     * Get page title
     * 
     * @string $page
     * @return string
     */
    private function getPageTitle($page)
    {
        $title = p_get_metadata($page, 'title');
        return $title ? $title : ucfirst(str_replace(['-', '_'], ' ', noNSorNS($page)));
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
        $user_data      = ($this->getConf('hideMail') ? ['mail' => null] : $auth->getUserData($this->getAuthorID()));
        $license_url    = (($license !== null) and array_key_exists('url', $license)) ? $license['url'] : null;
        $page_url       = wl($this->page, '', true);
        $description    = str_replace("\n", ' ', $this->getDescription());
        $created        = date(DATE_W3C, $this->getCreatedDate());
        $modified       = date(DATE_W3C, $this->getModifiedDate());
        $title          = (isset($this->meta['title']) ? $this->meta['title'] : $this->page);
        $wiki_logo_info = [];
        $wiki_logo      = tpl_getMediaFile([':wiki:logo.png', ':logo.png', 'images/logo.png'], true, $wiki_logo_info);

        $json_ld = [
            '@type'         => $type,
            'headline'      => $title,
            'name'          => $title,
            'datePublished' => $created,
            'dateCreated'   => $created,
            'dateModified'  => $modified,
            'description'   => $description,
            'license'       => $license_url,
            'url'           => $page_url,
            'inLanguage'    => $conf['lang'],

            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $page_url,
            ],

            'publisher' => [
                '@type' => 'Organization',
                'name'  => $conf['title'],
                'logo'  => [
                    '@type' => 'ImageObject',
                    'url'   => $wiki_logo,
                ],
            ],

            'isPartOf' => [
                '@type' => 'WebSite',
                'url'   => DOKU_URL,
                'name'  => $conf['title'],
            ]

        ];

        if ($tags = $this->getTags()) {
            $json_ld['keywords'] = implode(',', $tags);
        }

        if ($image_url = $this->getFirstImageURL()) {

            $image_info    = [];
            $article_image = tpl_getMediaFile([':' . $this->getFirstImage()], true, $image_info);

            $json_ld['image'] = [
                '@type'  => 'ImageObject',
                'url'    => $image_url,
                'width'  => $image_info[0],
                'height' => $image_info[1],
            ];
        } else {

            // Fallback
            //$json_ld['image'] = $json_ld['publisher']['logo'];

        }

        if ($author = $this->getAuthor()) {

            $json_ld['author'] = [
                '@type' => 'Person',
                'name'  => $author,
                'email' => $user_data['mail'],
            ];

            if (isset($this->meta['contributor'])) {
                foreach ($this->meta['contributor'] as $uid => $fullname) {

                    $contributor_data = ($this->getConf('hideMail') ? ['mail' => null] : $auth->getUserData($uid));

                    $json_ld['contributor'][] = [
                        '@type' => 'Person',
                        'name'  => $fullname,
                        'email' => $contributor_data['mail'],
                    ];
                }
            }
        }

        return $json_ld;
    }

    public function getJsonLD()
    {

        $json_ld = [];

        if ($data = $this->getStructuredData()) {
            $json_ld[] = $data;
        }

        if ($data = $this->getBreadcrumbs()) {
            $json_ld[] = $data;
        }

        if ($data = $this->getBacklinks()) {
            $json_ld[] = $data;
        }

        if ($data = $this->getWebSite()) {
            $json_ld[] = $data;
        }

        return $json_ld;
    }

    public function getBacklinks()
    {

        if (!$backlinks = ft_backlinks($this->page)) {
            return false;
        }

        $json_ld_webpage = [
            '@type' => 'WebPage',
        ];

        foreach ($backlinks as $pageid) {
            $json_ld_webpage['relatedLink'][] = wl($pageid, '', true);
        }

        if (isset($json_ld_webpage['relatedLink'])) {
            return $json_ld_webpage;
        }
    }

    public function getBreadcrumbs()
    {
        global $conf;

        if (!$conf['youarehere']) {
            return false;
        }

        $items = [];

        $items[] = [
            'id'   => $conf['start'],
            'name' => $this->getPageTitle($conf['start']),
        ];

        $parts = explode(':', $this->page);
        $count = count($parts);
        $page  = '';

        for ($i = 0; $i < $count - 1; $i++) {

            $part = $parts[$i];
            $page .= ":$part:" . $conf['start'];
            $page = cleanID($page);

            if ($page == $conf['start']) {
                continue;
            }

            if (! page_exists($page)) {
                continue;
            }

            $title = $this->getPageTitle($page);

            $items[] = [
                'id'   => $page,
                'name' => ($title ? $title : $part),
            ];
        }

        $items[] = [
            'id'   => $this->page,
            'name' => $this->getPageTitle($this->page),
        ];

        if (count($items) < 2) {
            return false;
        }

        $breadcrumb = [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [],
        ];

        $last_index = count($items) - 1;

        foreach ($items as $index => $item) {
            $list_item = [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $item['name'],
            ];

            if ($index !== $last_index) {
                $list_item['item'] = wl($item['id'], '', true);
            }

            $breadcrumb['itemListElement'][] = $list_item;
        }

        return $breadcrumb;
    }

    public function getDublinCore()
    {
        global $conf;

        if (!$this->meta) {
            return [];
        }

        $license      = $this->getLicense();
        $contributors = [];

        if (isset($this->meta['contributor']) && is_array($this->meta['contributor'])) {
            foreach ($this->meta['contributor'] as $uid => $fullname) {
                $contributors[] = $fullname;
            }
        }

        $dublin_core = [
            'DC.Title'        => $this->getTitle(),
            'DC.Description'  => str_replace("\n", ' ', $this->getDescription()),
            'DC.Publisher'    => $conf['title'],
            'DC.Creator'      => $this->getAuthor(),
            'DC.Contributor'  => $contributors,
            'DC.Language'     => $conf['lang'],
            'DC.Created'      => date(DATE_W3C, $this->getCreatedDate()),
            'DC.Modified'     => date(DATE_W3C, $this->getModifiedDate()),
            'DC.Date'         => date(DATE_W3C, $this->getCreatedDate()),
            'DC.Identifier'   => "urn:" . $this->page,
            'DC.Subject'      => $this->getTags(),
            'DC.Type'         => 'Text',
            'DC.Format'       => 'text/html',
        ];

        if (isset($license['name'])) {
            $dublin_core['DC.Rights'] = $license['name'];
        }

        return $dublin_core;
    }

    public function getOpenGraph()
    {
        global $conf;

        if (!$this->meta) {
            return [];
        }

        $locale = $conf['lang'];

        if ($locale == 'en') {
            $locale = 'en_GB';
        } else {
            $locale .= '_' . strtoupper($locale);
        }

        $open_graph = [
            'og:title'               => $this->getTitle(),
            'og:description'         => str_replace("\n", ' ', $this->getDescription()),
            'og:url'                 => wl($this->page, '', true),
            'og:type'                => 'article',
            'og:image'               => $this->getFirstImageURL(),
            'og:locale'              => $locale,
            'og:site_name'           => $conf['title'],

            'article:published_time' => date(DATE_W3C, $this->getCreatedDate()),
            'article:modified_time'  => date(DATE_W3C, $this->getModifiedDate()),
            'article:section'        => getNS($this->page),
            'article:author'         => $this->getAuthor(),
            'article:tag'            => $this->getTags(),
        ];

        return $open_graph;
    }
}
