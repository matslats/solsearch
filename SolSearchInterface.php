<?php


/**
 * Interface for SolSearch, to be connected to a REST API
 *
 * The SolAd type is an object with the following properties:
 * UUID
 * title
 * body
 * keywords
 * language
 * location
 * scope, integer from 0-3
 * expiry (when the scope goes to zero)
 * url
 */

interface SolSearchInterface {

  const SCOPE_PRIVATE = 0;
  const SCOPE_LOCAL = 1;
  const SCOPE_NETWORK = 2;
  const SCOPE_PUBLIC = 3;

  /**
   * Filter the database and return the results
   *
   * @param array $params
   *   the filters to apply to the search, namely
   * - bool local: true to include only local results
   * - floats circle: the lat, the lon and the km radius to search
   * - string text: a string to search for in title, description & keywords
   * - bool directexchange: TRUE to exclude transactionts that don't allow barter
   * - bool indirectexchange: TRUE to exclude transactions that don't allow ccs
   * - bool money: TRUE to exclude transactions that don't allow money
   *
   * @param string $sort_by
   *
   * @return array
   *   a list of the
   */
  public function filter($params, $sort_by = 'radius', $dir = 'ASC');

  /**
   * Preload the database with a lot of existing ads
   *
   * @param stdClass[] $ad
   *   The object to be added, including a UUID
   *
   * @return bool
   *   TRUE if the addition was successful.
   */
  public function bulkUpsert(stdClass $ads);

  /**
   * Delete many ads from the database
   *
   * @param string[] $uuids
   *
   * @return bool
   *   TRUE if the deletion was successful.
   */
  public function bulkDelete(array $uuids);

  /**
   * Update a single ad
   *
   * @param string $type
   *   The 'type' property
   * @param stdClass $ad
   *   The object to be added
   *
   * @return bool
   *   TRUE if the update was successful
   */
  public function upsert($type, stdClass $ad);


  /**
   * Admin only. Add a new group to the database. This must be done before any
   * of that groups ads are added. Admin only.
   *
   * @param string $url
   * @param string $name\
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

  /**
   * Admin only. Show a list of all the connected clients;
   */
  public function listCLients();

}