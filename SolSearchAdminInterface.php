<?php

require_once 'SolSearchInterface.php';

/**
 * Interface for SolSearch, to be connected to a REST API
 */

interface SolSearchAdminInterface extends SolsearchInterface{

  /**
   * Add a new group to the database. This must be done before any
   * of that groups ads are added. Admin only.
   *
   * @param string $url
   * @param string $name
   *
   * @return string
   *   The new API Key
   */
  public function insertClient($url, $name);

  /**
   * Admin only.  Remove a client and all its ads from the db.
   *
   * @param string $apikey
   * @param string $url
   */
  public function deleteClient($apikey);

  /**
   * Admin only. Update a client's name or url
   */
  public function updateClient($id, $url, $name);

}