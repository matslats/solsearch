<?php

require 'SolSearchAdminInterface.php';
require 'SolSearch.php';

/*
 * <b>Fatal error</b>:  Class SolSearchAdmin contains 4 abstract methods and
 * must therefore be declared abstract or implement the remaining methods (
 * SolSearchAdminInterface::insertGroup,
 * SolSearchAdminInterface::deleteGroup,
 * SolSearchAdminInterface::updateGroup
 */

class SolSearchAdmin extends Solsearch implements SolSearchAdminInterface {

  /**
   * Admin only. Add a new group to the database. This must be done before any
   * of that groups ads are added. Admin only.
   *
   * @param string $url
   * @param string $name
   */
  public function insertGroup($url, $name) {
    $apikey = $this->makeAPIkey();
    $result = $this->dbQuery("INSERT INTO clients (apikey, name, url) VALUES ('$apikey', '$name', '$url')");
    return $apikey;
  }

  /**
   * Admin only.  Remove a client and all its ads from the db.
   *
   * @param string $apikey
   */
  public function deleteGroup($id) {
     $this->dbQuery("DELETE FROM clients WHERE id = '$id'");
     $this->dbQuery("DELETE FROM ads WHERE client_id = '$idd'");
  }

  /**
   * Admin only. Update a client's name or url
   */
  public function updateGroup($id, $name, $url) {
    $query = "UPDATE clients SET url = '$url', name = '$name' WHERE id = '$id'";
    $this->dbQuery($query);
  }


  public function listGroups() {
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
}
