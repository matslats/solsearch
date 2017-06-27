<?php
/**
 * Class for the ad when it is output. On input there is no id and the client id
 * and name are inferred from the authentication
 */

class SolAd {


  /**
   * A unique internal id of the ad
   * @var int
   */
  public $id;

  /**
   * A unique internal id of the exchange that owns this ad
   * @var int
   */
  private $client_id;

  /**
   * The name of the exchange which owns this add
   * @var string
   */
  public $name;

  /**
   * The absolute url of the ad on its native site
   * @var string
   */
  public $url;

  /**
   * The type of ad. Currently restricted to 'offer' or 'want'
   * @var string
   */
  public $type;

  /**
   * two letter language code
   * @var string
   */
  public $lang;

  /**
   * The title of the ad
   * @var string
   */
  public $title;

  /**
   * The body of the add, with sanitised html
   * @var string
   */
  public $body;

  /**
   * Some comma separated keywords
   * @var string
   */
  public $keywords;

  /**
   * TRUE if the owner of the add wants to barter
   * @var bool
   */
  public $directexchange;

  /**
   * TRUE if the owner of the add wants complementary currency
   * @var bool
   */
  public $indirectexchange;

  /**
   * TRUE if the owner of the add expects part-payment with money
   * @var bool
   */
  public $money;

  /**
   * A code indicating the visibility of the ad
   * 3 = visible only to the network
   * 4 = visible to the general public
   * @var int
   */
  public $scope;

  /**
   * A universally unique id for the ad
   * @var string
   */
  private $uuid;

  /**
   * Unixtime when the ad should be automatically delisted (either with cron, or
   * by filtering the search)
   * @var int
   */
  private $expires;

  /**
   * A POINT on the globe where the ad is from. E.g. POINT (0 50)
   * @var string
   *
   * @note is there another primitive type for this?
   */
  public $loc;


}