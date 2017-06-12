<?php

namespace Drupal\smallads_index;

class SolSearch implements \Drupal\smallads_index\SolSearchInterface {

  /**
   * The API key of the group
   * @var string
   */
  private $groupUuid;

  /**
   * The database connection
   * @var Resource
   */
  private $connection;

  function __construct($group_uuid, $connection) {
    $this->groupUuid = $group_uuid;
    $this->connection = $connection;
  }

  /**
   * the type, e.g. offer, want
   */
  private $type;

  function init($type) {
    $this->type = $type;
  }

  /**
   * Filter the database and return the results
   */
  public function filter($params) {

  }

  /**
   * Preload the database with a lot of existing ads
   */
  public function bulkUpsert(array $ads) {
     foreach ($ads as $ad) {
       $this->validateSolAd($ad);
     }
  }

  /**
   * Delete many ads from the database
   */
  public function bulkDelete(array $uuids) {

  }

  /**
   * Update a single ad
   */
  public function upsert(SolAd $ad) {
     $this->validateSolAd($ad);

  }

  /**
   * Check all the fields have good data
   *
   * @param \Drupal\smallads_index\SolAd $ad
   *
   * @throws Exception
   *
   * UUID
   * title
   * body
   * keywords
   * language?
   * location
   * scope, integer from 0-3
   * expiry (when the scope goes to zero)
   * url
   */
  private function validateSolAd(SolAd $ad) {
    //check the format of UUID - what is the name of that format?

    //$title is max 100 chars
    if (strlen($ad->title) > 100) {
      $ad->title = substr($ad->title, 0, 100);
    }
    //sanitise the body

    //keywords should be comma separated alphanumeric

    if (empty($ad->location['lat']) or empty($ad->location['lon'])) {
      $messages[] = 'Lat and Lon MUST be populated.';
    }
    if ($ad->location['lat'] > 90 or $ad->location['lat'] < -90) {
      throw new exception('Latitude out of range. should be -90 < 90');
    }
    if ($ad->location['lat'] > 180 or $ad->location['lat'] < -180) {
      throw new exception('Longitude out of range. should be -180 < 180');
    }
    if (!is_numeric($ad->scope) or $ad->scope < 0 or $ad->scope > 3) {
      throw new exception('Scope out of range. should be a number from 0-3');
    }
    $limit = strtotime('+1 year');
    if ($ad->expiry > $limit + 86400) {//a year and a day
      $ad->expiry = strtotime('+1 year');
      $messages[] = 'Expiry has been curtailed to 1 year hence';
    }
    //get a REGEX for url parsing.
    if (!preg_match('/\.[az]{2}^/', $ad->url)) {
      throw new exception('Not a valid url');
    }
    $ad->url = str_replace(array('http://', 'https://'), array('', ''), $ad->url);
  }


  /**
   * Admin only. Add a new group to the database. This must be done before any
   * of that groups ads are added. Admin only.
   *
   * @param string $apikey
   * @param string $url
   * @param string $name
   */
  public function addClient($apikey, $url, $name) {

  }

  /**
   * Admin only.  Remove a client and all its ads from the db.
   *
   * @param string $apikey
   */
  public function deleteClient($apikey) {

  }

  /**
   * Admin only. Update a client's name or url
   */
  public function updateClient($apikey, $url, $name) {

  }


  public function listCLients() {

  }

}