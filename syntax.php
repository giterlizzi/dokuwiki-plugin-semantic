<?php
/**
 * Semantic plugin: Add Schema.org News Article using JSON-LD
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 * @copyright  (C) 2015-2023, Giuseppe Di Terlizzi
 */

class syntax_plugin_semantic extends DokuWiki_Syntax_Plugin
{

    private $macros = array(
        '~~NewsArticle~~', '~~Article~~', '~~TechArticle~~',
        '~~BlogPosting~~', '~~Recipe~~', '~~NOSEMANTIC~~',
    );

    public function getType()
    {return 'substition';}
    public function getSort()
    {return 99;}

    public function connectTo($mode)
    {

        foreach ($this->macros as $macro) {
            $this->Lexer->addSpecialPattern($macro, $mode, 'plugin_semantic');
        }

    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        return array($match, $state, $pos);
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {

        if ($mode == 'metadata') {

            list($match, $state, $pos) = $data;

            if ($match == '~~NOSEMANTIC~~') {
                $renderer->meta['plugin']['semantic']['enabled'] = false;
            } else {
                $renderer->meta['plugin']['semantic']['schema.org']['type'] = trim(str_replace('Schema.org/', '', $match), '~~');
                $renderer->meta['plugin']['semantic']['enabled']            = true;
            }

        }

        return false;

    }

}
