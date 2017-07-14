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
   * List and search ads
   *
   * @param string $type
   *   Ad type. See data type docs. NULL or '0' means 'all'.
   * @param array $params
   *   Associative array of the filters to apply to the search, namely
   * - bool local: true to include only local results
   * - floats circle: the lat, the lon and the km radius to search
   * - string text: a string to search for in title, description & keywords
   * - bool directexchange: TRUE to exclude transactionts that don't allow barter
   * - bool indirectexchange: TRUE to exclude transactions that don't allow ccs
   * - bool money: TRUE to exclude transactions that don't allow moneyi
   *
   * @todo HOW ARE THESE APPLIED IF MORE THAN ONE IS SPECIFIED?
   *
   * @param int $limit
   * @param int $offset
   * @param string $sort_by
   *
   * @return array
   *   A list of SolAd objects.
   */
  public function searchAds($type, $params, $offset = 0, $limit = 10, $sort_by = 'expires,asc');

  /**
   * Get one ad by UUID
   *
   * @param string $uuid
   *
   * @return SolAd returns one SolAd object, or NULL
   */

  public function getAd($uuid);

  /**
   * Update one ad.
   *
   * @param SolAd $ads
   *
   * @return bool
   *   TRUE if the update was successful
   */
  public function updateAd(SolAd $ad);


  /**
   * Update many ads
   *
   * $param array $ads An array of SolAd objects
   *
   * @return bool
   *   TRUE if the update was successful
   */
   public function bulkUpdateAds(array $ads);


  /**
   * Insert one ad.
   *
   * @param SolAd $ads
   *
   * @return bool
   *   TRUE if the insert was successful
   */
  public function insertAd(SolAd $ad);


  /**
   * Delete an ad.
   *
   * @param string $uuid
   *
   * @return bool
   *   TRUE if the delete was successful
   */
  public function deleteAd($uuid);

  /**
   * @param SolAd $ads
   * Delete one or more ads.
   *
   * @param string[] $uuids
   *
   * @return bool
   *   TRUE if *ALL* deletes were successful, does NOT do partial delete if failure of any single deletion
   */
  public function bulkDeleteAds(array $uuids);


  /**
   * Allow a group to remove itself
   */
  public function bye();

}
