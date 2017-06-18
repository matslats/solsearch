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
  "scope": "3"
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
   *
   * @note Not currently possible to order by distance. Radius uses a box not a circle.
   */
  public function filter($type, $params, $offset = 0, $limit = 10, $sort_by = 'expires,asc') {
    $query = "SELECT id, type, title, body, keywords, directexchange, indirectexchange, money, scope, uuid, expires, path, client_id, X(location) as lon, Y(location) as lat"
        . " FROM ads WHERE type = '$type'";
    if (!empty($params['fragment'])) {
      $like = '%'. $params['fragment'] .'%';
      $query .= " AND (title LIKE '$like' OR body LIKE '$like' or keywords LIKE '$like') ";
    }
    // scope 0 means unpublished and 1 means neighbourhood Neither supported here at the moment
    $query .= " AND (scope = 4 OR (client_id = $this->groupId AND scope = 3)) ";

    if (!empty($params['circle'])) {
      list($lat, $lon, $radius) = explode(',', $params['circle']);
      // MAKE AN box instead of filtering by radius.
      $rlon1 = $lon-$radius/abs(cos(radians($lat))*111);
      $rlon2 = $lon+$radius/abs(cos(radians($lat))*111);
      $rlat1 = $lat-($radius/111);
      $rlat2 = $lat+($radius/111);
      $query .=  " AND st_within(shape, envelope(linestring(point($rlon1, $rlat1), point($rlon2, $rlat2))))";
    }
    $flags = array_keys(array_filter([
      'directexchange' => @$params['directexchange'],
      'indirectexchange' => @$params['indirectexchange'],
      'money' => @$params['money']
    ]));
    if ($flags) {
      foreach($flags as $key) {
        $clause[] = " $key = 1";
      }
      $query .= ' AND ('.implode(' OR ', $clause).')';
    }
      // no flags means free things only.
      //$query .= ' AND directexchange = 0 AND indirectexchange =0 AND money =0';
    if (isset($params['directexchange']) and $params['directexchange'] == 0) {
      $query .= ' AND directexchange = 0 ';
    }
    if (isset($params['indirectexchange']) and $params['indirectexchange'] == 0) {
      $query .= ' AND indirectexchange = 0 ';
    }
    if (isset($params['money']) and $params['money'] == 0) {
      $query .= ' AND money = 0 ';
    }
    //Sorting
    list($field, $dir) = explode(',', $sort_by.',ASC');
    if ($field == 'distance' and isset($params['circle'])) {
      // Not yet supported, and not very necessary for map views
    }
    else {
      $query .= " ORDER BY $field $dir";
    }

    if ($limit) {
      $query .= " limit $limit";
    }
    if ($limit && $offset) {
      $query .= ", $offset";
    }
    $result = $this->dbQuery($query);

    $output = [];
    while ($row = mysql_fetch_object($result)) {
      $output[] = $row;
    }
    return $output;
  }

  /**
   * Delete many ads from the database
   */
  public function delete(array $uuids) {
    foreach ($uuids as $uuid) {
      $in[] = "'".$uuid."'";
    }
    $this->dbQuery("DELETE FROM ads WHERE uuid IN (".implode($in).")");
  }

  /**
   * Update a single ad
   *
   * @param string $type
   * @param stdClass[] $ads
   */
  public function upsert($type, array $ads) {
    //todo check the uuid and either insert or update
    foreach ($ads as $ad) {
      $this->validateSolAd($ad);
      list($lat, $lon) = explode(',', $ad->location);
      $location = "ST_GeomFromText('POINT($lon $lat)')";
      $query = "REPLACE INTO ads
        (`uuid`, `type`, `title`, `body`, `keywords`, `image_path`, `directexchange`, `indirectexchange`, `money`, `scope`, `location`, `expires`, `path`, `client_id`)
        VALUES ('$ad->uuid', '$type', '$ad->title', '$ad->body', '$ad->keywords', '$ad->image', '$ad->directexchange', '$ad->indirectexchange', '$ad->money', '$ad->scope', $location, '$ad->expires', '$ad->path', '$this->groupId')";
      $this->dbQuery($query);
      $this->log("inserted $uuid");
    }
  }

  /**
   * Check all the fields have good data
   *
   * @param \Drupal\smallads_index\SolAd $ad
   *
   * @throws Exception
   *
   */
  private function validateSolAd(stdClass $ad) {
    //check all the fields exist
    $fields = ['uuid', 'title', 'body', 'keywords', 'created', 'expires', 'location', 'directexchange', 'indirectexchange', 'money', 'path'];
    foreach ($fields as $fieldname) {
      if (!isset($ad->{$fieldname})) {
        throw new \Exception("$fieldname not found on ad");
      }
    }
    //
    //
    //check the format of UUID - what is the name of that format?

    //$title is max 100 chars
    if (strlen($ad->title) > 128) {
      //We don't have a way to send warnings.
      $ad->title = substr($ad->title, 0, 128);
    }
    //sanitise the body


    list($lat, $lon) = explode(',', $ad->location);
    if ($lat > 90 or $lat < -90) {
      throw new exception('Latitude out of range. should be -90 < 90');
    }
    if ($lon > 180 or $lon < -180) {
      throw new exception('Longitude out of range. should be -180 < 180');
    }
    if (!is_numeric($ad->scope) or $ad->scope < 3 or $ad->scope > 4) {
      throw new exception('Scope out of range. should be a number from 3-4: '.$ad->scope);
    }
    $limit = strtotime('+1 year');
    if ($ad->expires > $limit + 86400) {//a year and a day
      $ad->expires = strtotime('+1 year');
      $messages[] = 'Expiry has been curtailed to 1 year hence';
    }

    //put in the default group
    $ad->group_id = $this->groupId;
    $ad->type = $this->type;
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
    $result = $this->dbQuery("INSERT INTO clients (apikey, name, url) VALUES ('$apikey', '$name', '$url')");
    return $apikey;
  }

  /**
   * Admin only.  Remove a client and all its ads from the db.
   *
   * @param string $apikey
   */
  public function deleteClient($id) {
     $this->dbQuery("DELETE FROM clients WHERE id = '$id'");
     $this->dbQuery("DELETE FROM ads WHERE client_id = '$idd'");
  }

  /**
   * Admin only. Update a client's name or url
   */
  public function updateClient($id, $name, $url) {
    $query = "UPDATE clients SET url = '$url', name = '$name' WHERE id = '$id'";
    $this->dbQuery($query);
  }


  public function listClients() {
    $result = $this->dbQuery(
      "SELECT c.id, c.name, c.url FROM clients c LEFT JOIN ads a ON c.id = a.client_id GROUP BY c.id, a.type"
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

  private function dbQuery($sql) {
    try {
      $this->log($sql);
      return $this->connection->query($sql);
    }
    // @todo wat? why catch and then throw exception?
    catch(PDOException $e) {
      throw $e;
    }
  }

  public function log($message) {
    $query = "INSERT INTO log (message, client_id) VALUES ('".addslashes($message)."', $this->groupId)";
    mysql_query($query, $this->connection);
  }

  public function getTypes() {
    $query = "SELECT type from ads GROUP BY type";
    $result = $this->dbQuery($query);
    while ($type = mysql_fetch_field($result)) {
      $types[] = $type;
    }
    return $types;
  }
}
