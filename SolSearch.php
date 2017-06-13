<?php

require 'SolSearchInterface.php';

/**
 * Example
{
  "uuid": "9328cchql09238374ncis73",
  "title": "Mind adventures",
  "body": "litle bit of escaped <strong>html</strong>",
  "keywords":  "blah, blue, blow",
  "location": "60,1"
  "scope": "2"
  "path": "node/99",
  "directexchange": "true",
  "indirectexchange": "true",
  "money": "false",
  "expires": 15000000000
}
 */

class SolSearch implements SolSearchInterface {

  /**
   * The API key of the group
   * @var string
   */
  private $groupId;

  /**
   * The database connection
   * @var Resource
   */
  private $connection;

  /**
   * the type, e.g. offer, want
   */
  private $type;

  function __construct($connection, $client, $type) {
    $this->connection = $connection;
    $this->groupId = $client->id;
    $this->type = $type;
  }


  /**
   * Filter the database and return the results
   */
  public function filter($params, $sort_by = 'radius', $dir = 'ASC') {
    $query = "SELECT * FROM ads WHERE type = '".$params['type']."'";
    $result = mysql_query($query, $this->connection);
    while ($row = mysql_fetch_object($result)) {
      $output[] = $row;
    }
    return $output;
  }

  /**
   * Preload the database with a lot of existing ads
   */
  public function bulkUpsert(stdClass $ads) {
     foreach ($ads as $ad) {
       $this->upsert($ad);
     }
  }

  /**
   * Delete many ads from the database
   */
  public function bulkDelete(array $uuids) {

  }

  /**
   * Delete many ads from the database
   */
  public function delete($uuid) {
    mysql_query("DELETE FROM ads WHERE uuid = '$uuid'");
  }

  /**
   * Update a single ad
   */
  public function upsert($type, stdClass $ad) {
    $this->validateSolAd($ad);
    $this->delete($ad->uuid);
    list($lat, $lon) = explode(',', $ad->location);print_R($ad);
    $query = "INSERT INTO ads (`type`, `title`, `body`, `keywords`, `directexchange`, `indirectexchange`, `money`, `scope`, `uuid`, `lat`, `lon`, `expires`, `path`, `client_id`)
      VALUES ('$type', '$ad->title', '$ad->body', '$ad->keywords', '$ad->directexchange', '$ad->indirectexchange', '$ad->money', '$ad->scope', '$ad->uuid', '$lat', '$lon', '$ad->expires', '$ad->path', '$this->groupId')";

    mysql_query($query, $this->connection);
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
  private function validateSolAd(stdClass $ad) {
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
    list($lat, $lon) = explode(',', $ad->location);
    if ($lat > 90 or $lat < -90) {
      throw new exception('Latitude out of range. should be -90 < 90');
    }
    if ($lon > 180 or $lon < -180) {
      throw new exception('Longitude out of range. should be -180 < 180');
    }
    if (!is_numeric($ad->scope) or $ad->scope < 0 or $ad->scope > 3) {
      throw new exception('Scope out of range. should be a number from 0-3');
    }
    $limit = strtotime('+1 year');
    if ($ad->expires > $limit + 86400) {//a year and a day
      $ad->expires = strtotime('+1 year');
      $messages[] = 'Expiry has been curtailed to 1 year hence';
    }
  }


  /**
   * Admin only. Add a new group to the database. This must be done before any
   * of that groups ads are added. Admin only.
   *
   * @param string $apikey
   * @param string $url
   * @param string $name
   */
  public function insertClient($url, $name) {
    $apikey = $this->makeAPIkey();
    $result = mysql_query(
      "INSERT INTO clients (apikey, name, url) VALUES ('$apikey', '$name', '$url')",
      $this->connection
    );
    //$client = mysql_fetch_object($result);
    return $apikey;
  }

  /**
   * Admin only.  Remove a client and all its ads from the db.
   *
   * @param string $apikey
   */
  public function deleteClient($ud) {
     mysql_query("DELETE FROM clients WHERE id = '$id'", $this->connection);
     mysql_query("DELETE FROM ads WHERE client_id = '$idd'", $this->connection);
  }

  /**
   * Admin only. Update a client's name or url
   */
  public function updateClient($id, $name, $url) {
    $query = "UPDATE clients SET url = '$url', name = '$name' WHERE id = '$id'";
    mysql_query($query, $this->connection);
  }


  public function listCLients() {
    $result = mysql_query(
      "SELECT c.id, c.name, c.url FROM clients c LEFT JOIN ads a ON c.id = a.client_id GROUP BY c.id, a.type",
      $this->connection
    );
    while ($item = mysql_fetch_object($result)) {
      $table[] = $item;
    }
    return $table;
  }

  private function makeAPIkey() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randstring = '';
    for ($i=0; $i<12; $i++) {
      $randstring .= $characters[rand(0, strlen($characters))];
    }
    return $randstring;

  }
}