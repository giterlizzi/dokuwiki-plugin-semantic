<?php
/**
 * Semantic Action Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @copyright  (C) 2015-2019, Giuseppe Di Terlizzi
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

/**
 * Class Semantic Action Plugin
 *
 * Add semantic data to DokuWiki
 */
class action_plugin_semantic extends DokuWiki_Action_Plugin
{

    private $helper = null;

    public function __construct()
    {
        $this->helper = $this->loadHelper('semantic');
    }

    /**
     * Register events
     *
     * @param Doku_Event_Handler $controller handler
     */
    public function register(Doku_Event_Handler $controller)
    {

        if ($this->getConf('useJSONLD')) {
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'website');
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'json_ld');
        }

        if ($this->getConf('useMetaDescription')) {
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_description');
        }

        if ($this->getConf('useMetaAuthor')) {
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_author');
        }

        if ($this->getConf('useDublinCore')) {
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_dublin_core');
        }

        if ($this->getConf('useOpenGraph')) {
            $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_open_graph');
        }

        if ($this->getConf('exposeWebService')) {
            $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'ajax');
        }

        $controller->register_hook('DOKUWIKI_STARTED', 'AFTER', $this, 'jsinfo');
    }

    /**
     * Export JSON-JD in $JSONINFO array
     */
    public function jsinfo(Doku_Event &$event, $param)
    {

        global $JSINFO;

        $JSINFO['plugin']['semantic'] = array(
            'exposeWebService' => $this->getConf('exposeWebService'),
        );

    }

    /**
     * Export in JSON-LD format
     *
     * @param Doku_Event $event handler
     * @param array      $param
     *
     * @return string
     */
    public function ajax(Doku_Event &$event, $param)
    {

        if ($event->data !== 'plugin_semantic') {
            return false;
        }

        //no other ajax call handlers needed
        $event->stopPropagation();
        $event->preventDefault();

        global $INPUT;

        $export = $INPUT->str('export');
        $id     = $INPUT->str('id');

        if (!$id) {
            return false;
        }

        $this->helper->getMetadata($id);
        $json_ld = $this->helper->getJsonLD();

        $json = new JSON();

        header('Content-Type: application/ld+json');
        print $json->encode($json_ld);
        return true;

    }

    /**
     * Expose JSON-JD WebSite schema
     *
     * @param Doku_Event $event handler
     * @param array      $params
     *
     * @return void
     */
    public function website(Doku_Event &$event, $params)
    {
        $event->data["script"][] = array(
            "type"  => "application/ld+json",
            "_data" => json_encode($this->helper->getWebSite(), JSON_PRETTY_PRINT),
        );
    }

    /**
     * JSON-LD Event handler
     *
     * @param Doku_Event $event handler
     * @param array      $params
     *
     * @return void
     */
    public function json_ld(Doku_Event &$event, $params)
    {

        global $ID;

        $this->helper->getMetadata($ID);
        $json_ld = $this->helper->getJsonLD();

        if (!count($json_ld)) {
            return false;
        }

        $event->data["script"][] = array(
            "type"  => "application/ld+json",
            "_data" => json_encode($json_ld, JSON_PRETTY_PRINT),
        );

    }

    /**
     * Meta Description handler
     *
     * @param Doku_Event $event handler
     * @param array      $params
     *
     * @return void
     */
    public function meta_description(Doku_Event &$event, $params)
    {

        global $ID;

        $this->helper->getMetadata($ID);

        if ($description = $this->helper->getDescription()) {

            $description = str_replace("\n", ' ', $description);

            $event->data['meta'][] = array(
                'name'    => 'description',
                'content' => $description,
            );

        }

    }

    /**
     * Meta Description handler
     *
     * @param Doku_Event $event handler
     * @param array      $params
     *
     * @return void
     */
    public function meta_author(Doku_Event &$event, $params)
    {

        global $ID;

        $this->helper->getMetadata($ID);

        if ($author = $this->helper->getAuthor()) {

            $event->data['meta'][] = array(
                'name'    => 'author',
                'content' => $author,
            );

        }

    }

    /**
     * OpenGraph handler
     *
     * @param Doku_Event $event handler
     * @param array      $params
     *
     * @return void
     */
    public function meta_open_graph(Doku_Event &$event, $params)
    {

        global $ID;

        $this->helper->getMetadata($ID);

        foreach ($this->helper->getOpenGraph() as $property => $content) {

            if (!$content) {
                continue;
            }

            $event->data['meta'][] = array(
                'property' => $property,
                'content'  => $content,
            );

        }

    }

    /**
     * Dublin Core handler
     *
     * @param Doku_Event $event handler
     * @param array      $params
     *
     * @return void
     */
    public function meta_dublin_core(Doku_Event &$event, $params)
    {

        global $ID;

        $this->helper->getMetadata($ID);

        foreach ($this->helper->getDublinCore() as $name => $content) {

            if (!$content) {
                continue;
            }

            $event->data['meta'][] = array(
                'name'    => $name,
                'content' => $content,
            );

        }

    }

}
