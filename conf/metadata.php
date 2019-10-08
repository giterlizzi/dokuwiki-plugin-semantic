<?php
/**
 * Options for the icons plugin
 *
 * @author Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 */

$meta['useMetaDescription']   = array('onoff');
$meta['useMetaAuthor']        = array('onoff');
$meta['useDublinCore']        = array('onoff');
$meta['useOpenGraph']         = array('onoff');
$meta['useJSONLD']            = array('onoff');
$meta['exposeWebService']     = array('onoff');
$meta['defaultSchemaOrgType'] = array('multichoice','_choices' => array('Article', 'NewsArticle', 'TechArticle', 'BlogPosting', 'Recipe'));
$meta['excludedPages']        = array('regex');
$meta['hideMail']             = array('onoff');
