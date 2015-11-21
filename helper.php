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
  private $page = null;

  public function getMetadata($page) {

    global $INFO;
    global $ID;
    global $license;
    global $auth;
    global $conf;

    $this->page = cleanID($page);

    $auth_check = auth_quickaclcheck($this->page);

    if ((bool) preg_match('/'. trim($this->getConf('excludedPages')) .'/', $this->page)) return false;

    if (! $auth_check) return false;

    $this->meta = p_get_metadata($page);

    if (isset($this->meta['plugin']['semantic']['enabled']) && ! $this->meta['plugin']['semantic']['enabled']) {
      return false;
    }

    if (! isset($this->meta['date']) || $this->meta['date'] == '') return false;

    return $this->meta;

  }

  public function getSchemaOrgType() {

    return ((isset($this->meta['plugin']['semantic']['schema.org']['type']))
             ? $this->meta['plugin']['semantic']['schema.org']['type']
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

    global $auth;
    global $conf;

    if (! count($this->meta)) return false;

    $license     = $this->getLicense();
    $type        = $this->getSchemaOrgType();
    $user_data   = $auth->getUserData($this->getAuthorID());
    $license_url = $license['url'];
    $page_url    = wl($this->page, '', true);
    $image_url   = $this->getFirstImageURL();
    $description = $this->getDescription();
    $created     = date(DATE_W3C, $this->getCreatedDate());
    $modified    = date(DATE_W3C, $this->getModifiedDate());
    $title       = (isset($this->meta['title']) ? $this->meta['title'] : $this->page);

    $json_ld = array(
      '@context'      => 'http://schema.org',
      '@type'         => $type,
      'headline'      => $title,
      'name'          => $title,
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


  public function getJsonLD() {

    $json_ld = array();

    if ($structured_data = $this->getStructuredData()) {
      $json_ld[] = $structured_data;
    }

    if ($backlinks = $this->getBacklinks()) {
      $json_ld[] = $backlinks;
    }

    return $json_ld;

  }


  public function getBacklinks() {

    if (! $backlinks = ft_backlinks($this->page)) return false;

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

    if (! $this->meta) return array();

    $license = $this->getLicense();
    $contributors = array();

    if (isset($this->meta['contributor'])) {
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

}
