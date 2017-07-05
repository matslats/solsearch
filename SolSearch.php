<?php
//require_once 'SolSearchInterface.php';

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
  "expires": 15000000000,
  "type":"offer",
  "lang":"",
  "url": "http:\/\/matslats.net\/ad\/38"
}
 */

class SolSearch implements SolSearchInterface {

  /**
   * The API key of the group
   * @var string
   */
  private $group;

  /**
   * The database connection
   * @var Resource
   */
  private $connection;

  /**
   * File handle to logfile
   * @var string
   */
  private $logFileHandle;

  function __construct($connection, $logFileHandle, $group) {
    $this->connection = $connection;
    $this->logFileHandle = $logFileHandle;
    $this->group = $group;
  }


  /**
   * Get one ad. Returns ad object or null
   */

  public function getAd($uuid) {
    $query = "SELECT a.id, c.name,  c.url, type, lang, title, body, keywords, directexchange, indirectexchange, money, scope, uuid, expires, path, client_id, X(location) as lon, Y(location) as lat"
       . " FROM ads a LEFT JOIN clients c ON a.client_id = c.id WHERE uuid = '" . $uuid . "'";
    $result = $this->dbQuery($query);
    return $result ? $result->fetchObject() : FALSE;
  }

  /**
   * Filter the database and return the results
   *
   * @note Not currently possible to order by distance. Radius uses a box not a circle.
   */
  public function searchAds($type, $params, $offset = 0, $limit = 10, $sort_by = 'expires,asc') {
    $query = "SELECT a.id, c.name,  c.url, type, lang, title, body, keywords, directexchange, indirectexchange, money, scope, uuid, expires, path, client_id, X(location) as lon, Y(location) as lat"
        . " FROM ads a LEFT JOIN clients c ON a.client_id = c.id WHERE a.client_id = c.id AND type = '$type'";

    if (!empty($params['fragment'])) {
      $like = '%'. $params['fragment'] .'%';
      $query .= " AND (title LIKE '$like' OR body LIKE '$like' or keywords LIKE '$like') ";
    }
    // scope 0 means unpublished and 1 means neighbourhood Neither supported here at the moment
    $query .= " AND (scope = 4 OR (client_id = ".$this->group->id." AND scope = 3)) ";

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
    if (isset($params['lang'])) {
      $query .= ' AND lang = '.$params['lang'];
    }
    $result = $this->dbQuery($query);
    $total = $result->rowCount();

    //Sorting
    list($field, $dir) = explode(',', $sort_by.',ASC');
    if ($field == 'distance' and isset($params['circle'])) {
      // Not yet supported, and not very necessary for map views
    }
    else {
      $query .= " ORDER BY $field $dir";
      $recalc = TRUE;
    }

    if ($limit) {
      $query .= " limit $limit";
      $recalc = TRUE;
    }

    if ($limit && $offset) {
      $query .= ", $offset";
    }

    if ($recalc) {
      $result = $this->dbQuery($query);
    }
    $items = [];

    while ($row = $result->fetchObject()) {
      $row->url = 'http://'.$row->url.'/'.$row->path;
      unset($row->path);
      $items[] = $row;
    }
    return ['total' => $total, 'items' => $items];
  }

  public function updateAd(SolAd $ad) {
    return $this->upsert($ad->type, [$ad]);
  }

  public function bulkUpdateAds(array $ads) {
    return $this->upsert($ad->type, [$ads]);
  }

  public function insertAd(SolAd $ad) {
    return $this->upsert($ad->type, [$ad]);
  }

  public function bulkInsertAds(array $ads) {
    return $this->upsert($ad->type, [$ads]);
  }

  public function deleteAd($uuid) {
    $this->bulkDeleteAds([$uuid]);
  }

  public function bulkDeleteAds(array $uuids) {
    foreach ($uuids as $uuid) {
      $in[] = "'".$uuid."'";
    }
    $this->dbQuery("DELETE FROM ads WHERE uuid IN (".implode(',', $in).")");
  }

  /**
   * Update a single ad
   *
   * @param string $type
   * @param stdClass[] $ads
   *
   * @return bool
   *   TRUE if the operation was successful.
   */
  public function upsert($type, array $ads) {
    //todo check the uuid and either insert or update
    foreach ($ads as $ad) {
      $this->validateSolAd($ad);
      //list($lat, $lon) = explode(',', $ad->location);
      $location = "ST_GeomFromText('$ad->location')";
      $query = "REPLACE INTO ads
        (`uuid`, `type`, `title`, `body`, `keywords`, `image_path`, `directexchange`, `indirectexchange`, `money`, `scope`, `location`, `expires`, `path`, `client_id`, `lang`)
        VALUES ('$ad->uuid', '$type', '$ad->title', '$ad->body', '$ad->keywords', '$ad->image', $ad->directexchange, $ad->indirectexchange, $ad->money, '$ad->scope', $location, '$ad->expires', '$ad->path', ".$this->group->id.", '$ad->lang')";
      $result = $this->dbQuery($query);
    }
    return is_object($result) && !mysql_error($result);
  }

  /**
   * Admin only.  Remove a client and all its ads from the db.
   *
   * @param string $apikey
   */
  public function deleteGroup() {
    // Only possible to delete your own group.
    $id = $this->group->id;
    $this->dbQuery("DELETE FROM clients WHERE id = '$id'");
    $this->dbQuery("DELETE FROM ads WHERE client_id = '$id'");
  }

  protected function dbQuery($sqlString) {
    try {
      $this->log($sqlString);
      return $this->connection->query($sqlString);
    }
    // @todo  does this need to be logged to file?
    catch(PDOException $e) {
      $this->log('Error: ', $e->getMessage());
    }
  }

  // @todo replace this with proper logging
  public function log($message) {
    if (!is_string($message))print_r(array_slice(debug_backtrace(), 0, 3));
    fputs($this->logFileHandle, $message . "\n");
  }

}
