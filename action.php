<?php
/**
 * Semantic Action Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
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

    if ($this->getConf('useMetaDescription')) {
      $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_description');
    }

    if ($this->getConf('useMetaAuthor')) {
      $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_author');
    }

    if ($this->getConf('useDublinCore')) {
      $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'meta_dublin_core');
    }

  }


  /**
   * JSON-LD Event handler
   *
   * @param  Doku_Event  &$event
   */
  public function json_ld(Doku_Event &$event, $param) {

    $helper = $this->loadHelper('semantic');

    if ($json_ld = $helper->getStructuredData()) {

      $event->data["script"][] = array (
        "type"  => "application/ld+json",
        "_data" => json_encode($json_ld),
      );

    }

    if ($json_ld_relations = $helper->getBacklinks()) {
      $event->data["script"][] = array (
        "type"  => "application/ld+json",
        "_data" => json_encode($json_ld_relations),
      );
    }

  }


  public function meta_description(Doku_Event &$event, $params) {

    $helper = $this->loadHelper('semantic');

    if ($description = $helper->getDescription()) {

      $description = str_replace("\n", ' ', $description);

      $event->data['meta'][] = array(
        'name'    => 'description',
        'content' => $description,
      );

    }

  }


  public function meta_author(Doku_Event &$event, $params) {

    $helper = $this->loadHelper('semantic');

    if ($author = $helper->getAuthor()) {

      $event->data['meta'][] = array(
        'name'    => 'author',
        'content' => $author,
      );

    }

  }


  public function meta_dublin_core(Doku_Event &$event, $params) {

    $helper = $this->loadHelper('semantic');

    foreach ($helper->getDublinCore() as $name => $content) {

      if (! $content) continue;

      $event->data['meta'][] = array(
        'name'    => $name,
        'content' => $content,
      );

    }

  }

}
