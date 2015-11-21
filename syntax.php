<?php
/**
 * Semantic plugin: Add Schema.org News Article using JSON-LD
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 */
// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

class syntax_plugin_semantic extends DokuWiki_Syntax_Plugin {

  private $macros = array(
    '~~NewsArticle~~', '~~Article~~', '~~TechArticle~~',
    '~~BlogPosting~~', '~~Recipe~~', '~~NOSEMANTIC~~'
  );

  function getType() { return 'substition'; }
  function getSort() { return 99; }

  function connectTo($mode) {

    foreach ($this->macros as $macro) {
      $this->Lexer->addSpecialPattern($macro, $mode, 'plugin_semantic');
    }

  }

  function handle($match, $state, $pos, Doku_Handler $handler) {
    return array($match, $state, $pos);
  }

  function render($mode, Doku_Renderer $renderer, $data) {

    if ($mode == 'metadata') {

      list($match, $state, $pos) = $data;

      if ($match == '~~NOSEMANTIC~~') {
        $renderer->meta['plugin']['semantic']['enabled'] = false;
      } else {
        $renderer->meta['plugin']['semantic']['schema.org']['type'] = trim(str_replace('Schema.org/', '', $match), '~~');
        $renderer->meta['plugin']['semantic']['enabled'] = true;
      }

    }

    return false;

  }

}
