<?php


/**
 * Interface for SolSearch, to be connected to a REST API
 */

interface SolSearchInterface {

  const SCOPE_PRIVATE = 0;
  const SCOPE_LOCAL = 1;
  const SCOPE_NETWORK = 2;
  const SCOPE_PUBLIC = 3;

  /**
   * Filter the database and return the results
   *
   * @param string $type
   *   One of a few mutually exlcusive types e.g. offer/want
   * @param array $params
   *   the filters to apply to the search, namely
   * - bool local: true to include only local results
   * - floats circle: the lat, the lon and the km radius to search
   * - string text: a string to search for in title, description & keywords
   * - bool directexchange: TRUE to exclude transactionts that don't allow barter
   * - bool indirectexchange: TRUE to exclude transactions that don't allow ccs
   * - bool money: TRUE to exclude transactions that don't allow money
   * @param int $limit
   * @param int $offset
   * @param string $sort_by
   * @param string $dir
   *
   * @return array
   *   a list of the items
   */
  public function filter($type, $params, $offset = 0, $limit = 10, $sort_by = 'expires,asc');

  /**
   * Update one or more ads.
   *
   * @param string $type
   *   The 'type' property
   * @param stdClass[] $ads
   *
   * @return bool
   *   TRUE if the update was successful
   */
  public function upsert($type, array $ads);

  /**
   * Delete one or more ads.
   *
   * @param string[] $uuids
   *
   * @return bool
   *   TRUE if the update was successful
   */
  public function delete(array $uuids);

}