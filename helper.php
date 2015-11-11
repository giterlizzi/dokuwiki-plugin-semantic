<?php
/**
 * Semantic plugin: Add Schema.org News Article using JSON-LD
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Giuseppe Di Terlizzi <giuseppe.diterlizzi@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_semantic extends DokuWiki_Plugin {

  private $meta = array();

  public function __construct() {
    $this->meta = $this->getMetadata();
  }

  private function getMetadata() {

    global $INFO;
    global $ID;
    global $license;
    global $auth;
    global $conf;

    if ((bool) preg_match('/'. trim($this->getConf('excludedPages')) .'/', $ID)) return false;

    if (! $INFO['perm']) return false;

    $this->meta = $INFO['meta'];

    if (isset($this->meta['semantic']['enabled']) && ! $this->meta['semantic']['enabled']) {
      return false;
    }

    if (! isset($this->meta['date']) || $this->meta['date'] == '') return false;

    return $this->meta;

  }

  public function getSchemaOrgType() {
    return ((isset($this->meta['semantic']['schema.org']['type']))
             ? $this->meta['semantic']['schema.org']['type']
             : $this->getConf('defaultSchemaOrgType'));
  }

  public function getFirstImageURL() {
    return (($this->meta['relation']['firstimage']) ? ml($this->meta['relation']['firstimage'], '', true, '&amp;', true) : null);
  }

  public function getDescription() {
    return trim(ltrim($this->meta['description']['abstract'], @$this->meta['title']));
  }

  public function getAuthor() {
    return ($this->meta['creator'] ? $this->meta['creator'] : null);
  }

  public function getAuthorID() {
    return ($this->meta['user'] ? $this->meta['user'] : null);
  }

  public function getTitle() {
    return ($this->meta['title'] ? $this->meta['title'] : null);
  }

  public function getCreatedDate() {
    return $this->meta['date']['created'];
  }

  public function getModifiedDate() {
    return $this->meta['date']['modified'];
  }

  public function getLicense() {
    global $license;
    global $conf;
    return @$license[$conf['license']];
  }

  /**
   * Return JSON-LD structured data in according of selected Schema.org type
   *
   * @return array
   */
  public function getStructuredData() {

    global $INFO;
    global $ID;
    global $auth;
    global $conf;

    if (! count($this->meta)) return false;

    $license     = $this->getLicense();
    $type        = $this->getSchemaOrgType();
    $user_data   = $auth->getUserData($this->getAuthorID());
    $license_url = $license['url'];
    $page_url    = wl($ID, '', true);
    $image_url   = $this->getFirstImageURL();
    $description = $this->getDescription();
    $created     = date(DATE_W3C, $this->getCreatedDate());
    $modified    = date(DATE_W3C, $this->getModifiedDate());

    $json_ld = array(
      '@context'      => 'http://schema.org',
      '@type'         => $type,
      'headline'      => @$this->meta['title'],
      'name'          => @$this->meta['title'],
      'image'         => array($image_url),
      'datePublished' => $created,
      'dateCreated'   => $created,
      'dateModified'  => $modified,
      'description'   => $description,
      'license'       => $license_url,
      'url'           => $page_url,
    );

    if ($author = $this->getAuthor()) {

      $json_ld['creator'] = array(
        '@context' => 'http://schema.org',
        '@type'    => 'Person',
        'name'     => $author,
        'email'    => $user_data['mail']
      );

      if (isset($this->meta['contributor'])) {
        foreach ($this->meta['contributor'] as $uid => $fullname) {
          $contributor_data = $auth->getUserData($uid);
          $json_ld['contributor'][] = array(
            '@context' => 'http://schema.org',
            '@type'    => 'Person',
            'name'     => $fullname,
            'email'    => $contributor_data['mail']
          );
        }
      }

    }

    return $json_ld;

  }

  public function getBacklinks() {

    global $ID;

    if (! $backlinks = ft_backlinks($ID)) return false;

    $json_ld_webpage = array(
      '@context' => 'http://schema.org',
      '@type'    => 'WebPage'
    );

    foreach ($backlinks as $pageid) {
      $json_ld_webpage['relatedLink'][] = wl($pageid, '', true);
    }

    if (isset($json_ld_webpage['relatedLink'])) return $json_ld_webpage;

  }


  public function getDublinCore() {

    global $conf;
    global $ID;

    if (! $this->meta = $this->getMetadata()) return false;

    $license = $this->getLicense();
    $contributors = array();
    foreach ($this->meta['contributor'] as $uid => $fullname) {
      $contributors[] = $fullname;
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
      'DC.Identifier'   => "urn:$ID",
    );

    return $dublin_core;

  }

}
