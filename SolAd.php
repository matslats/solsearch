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

  // @todo make a sensible constructor

   /**
   * Check all the fields are set and have good data
   *
   * @param \Drupal\smallads_index\SolAd $ad
   *
   * @throws Exception
   *
   */
  public function validate() {
    //check all the fields exist
    $fields = ['uuid', 'title', 'body', 'keywords', 'created', 'expires', 'location', 'directexchange', 'indirectexchange', 'money', 'path'];
    
    $exceptions = new Array();

    foreach ($fields as $fieldname) {
      if (!isset($this->{$fieldname})) {
        $exceptions[] = "$fieldname not found on ad";
      }
    }
    

    //check the format of UUID - what is the name of that format?

    //$title is max 100 chars
    if (strlen($this->title) > 128) {
      //We don't have a way to send warnings.
      //@todo move fixing stuff somewhere else
      //$ad->title = substr($ad->title, 0, 128);
      $exceptions[] = "Title is too long";
    }
    //sanitise the body


    list($lat, $lon) = explode(',', $this->location);
    if ($lat > 90 or $lat < -90) {
      $exceptions[] = 'Latitude out of range. should be -90 < 90';
    }
    if ($lon > 180 or $lon < -180) {
      $exceptions[] = 'Longitude out of range. should be -180 < 180';
    }
    if (!is_numeric($this->scope) or $this->scope < 3 or $this->scope > 4) {
      $exceptions[] = 'Scope out of range. should be a number from 3-4';
    }
    $limit = strtotime('+1 year');
    if ($this->expires > $limit + 86400) {//a year and a day
      // @todo move fixing stuff somewhere else
      // $this->expires = strtotime('+1 year');
      $messages[] = 'Expiry has been curtailed to 1 year hence';
    }

    //put in the default group

    // @todo what's this? why doesn't the caller do it?
    $ad->group_id = $this->groupId;
    $ad->type = $this->type;
  }


}
