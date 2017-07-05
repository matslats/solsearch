<?php

require_once 'SolSearchInterface.php';

/**
 * Interface for SolSearch, to be connected to a REST API
 */

interface SolSearchAdminInterface extends SolSearchInterface {

  /**
   * Add a Group
   *
   * @param string $url
   * @param string $name
   *
   * @return array Associative array of
   *   'id' UUID for the new client
   *   'key' API key for the new client
   */
  public function insertGroup($url, $name);

  /**
   * Update a group's name or url
   */
  public function updateGroup($id, $url, $name);

  /**
   * Admin only. Show a list of all the groups
   *
   * @return array
   */
  public function listGroups();

}
