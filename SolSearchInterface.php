<?php

namespace Drupal\smallads_index;

interface SolSearchInterface {


  /**
   * Filter the database and return the results
   */
  public function filter($params);

  /**
   * Preload the database with a lot of existing ads
   */
  public function bulkAdd();

  /**
   * Delete many ads from the database
   */
  public function bulkDelete(array $uuids);

  /**
   * Update a single ad
   */
  public function update(SolAd $ad);



}