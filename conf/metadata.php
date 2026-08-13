<?php
/**
 * Options for the icons plugin
 *
 * @author Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 */

$meta['useMetaDescription']   = ['onoff'];
$meta['useMetaAuthor']        = ['onoff'];
$meta['useDublinCore']        = ['onoff'];
$meta['useOpenGraph']         = ['onoff'];
$meta['useJSONLD']            = ['onoff'];
$meta['exposeWebService']     = ['onoff'];
$meta['defaultSchemaOrgType'] = ['multichoice', '_choices' => ['Article', 'NewsArticle', 'TechArticle', 'BlogPosting', 'Recipe']];
$meta['excludedPages']        = ['regex'];
$meta['hideMail']             = ['onoff'];
$meta['showUserAs']           = ['multichoice', '_choices' => ['loginname', 'fullname']];
