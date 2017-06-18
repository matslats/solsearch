<?php

interface SolSearchAdminInterface extends SolSearchInterface {

  /**
   * Add a new client to the database. This must be done before any
   * of that groups ads are added. Admin only.
   *
   * @param string $url
   * @param string $name
   *
   * @return array Associative array of
   *   'id' UUID for the new client
   *   'key' API key for the new client
   */
  public function insertClient($url, $name);

  /**
   * Remove a client and all its ads from the db.
   *
   * @param string $clientId UUID of the client
   */
  public function deleteClient($id);

  /**
   * Update a client's name or url
   */
  public function updateClient($id, $url, $name);

  /**
   * Admin only. Show a list of all the clients;
   *
   * @return array
   */
  public function listClients();

}
