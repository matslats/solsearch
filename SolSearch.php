<?php
require_once 'SolSearchInterface.php';


class SolSearch implements SolSearchInterface {

  /**
   * The API key of the group
   * @var string
   */
  private $group;

  function __construct($group) {
    $this->group = $group;
  }


  /**
   * Get one ad. Returns ad object or null
   * @note Not currently used for anything
   */
  public function getAd($uuid) {
    $query = "SELECT a.id, c.name,  c.url, type, lang, title, body, keywords, directexchange, indirectexchange, money, scope, uuid, expires, path, client_id, X(location) as lon, Y(location) as lat, image "
       . " FROM ads a LEFT JOIN clients c ON a.client_id = c.id WHERE uuid = '" . $uuid . "'";
    $result = dbQuery($query);
    return $result ? $result->fetchObject() : FALSE;
  }

  /**
   * Filter the database and return the results
   *
   * @note Not currently possible to order by distance. Radius uses a box not a circle.
   */
  public function searchAds($type, $params, $offset = 0, $limit = 10, $sort_by = 'expires,asc') {
    $query = "SELECT a.id, c.name,  c.url, type, lang, title, body, keywords, directexchange, indirectexchange, money, scope, uuid, expires, path, client_id, X(location) as lon, Y(location) as lat, image "
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
    $result = dbQuery($query);
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
      $result = dbQuery($query);
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
    return $ad->update();
  }


  public function bulkUpdateAds(array $ads) {
    foreach ($ads as $ad) {
      if ($this->exists($ad->id)) {
        $this->updateAd($ad);
      }
      else {
         $this->insertAd($ad);
      }
    }
  }

  public function insertAd(SolAd $ad) {
    return $ad->insert();
  }

  public function deleteAd($id) {
    $this->bulkDeleteAds([$id]);
  }

  public function bulkDeleteAds(array $ids) {
    foreach ($ids as $id) {
      $in[] = "'".$id."'";
    }
    dbQuery("DELETE FROM ads WHERE client_id = ".$this->group->id." AND id IN (".implode(',', $in).")");
  }

  /**
   * Admin only.  Remove a client and all its ads from the db.
   *
   * @param string $apikey
   */
  public function bye() {
    // Only possible to delete your own group.
    $id = $this->group->id;
    dbQuery("DELETE FROM clients WHERE id = '$id'");
    dbQuery("DELETE FROM ads WHERE client_id = '$id'");
  }

  public function exists($id) {
    $query = "SELECT uuid FROM ads WHERE client_id = ".$this->group->id." AND id = $id";
    if ($result = dbQuery($query)) {
      return $result->rowCount();
    }
  }

}
