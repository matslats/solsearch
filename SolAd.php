<?php
/**
 * Class for the ad when it is output. On input there is no id and the client id
 * and name are inferred from the authentication
 *
  {
    "id": "85",
    "name": "admin",
    "url": "matslats.net",
    "type": "offer",
    "lang": "",
    "title": "A very nice gelatinous  ",
    "body": "Abico ea lenis wisi. Commoveo facilisi in praesent sit tation valetudo vicis vulputate ymo. Abico elit natu obruo ratis scisco vindico. Bene commodo diam paulatim. Diam et humo lenis lucidus neque occuro os patria vereor. Et immitto pala. Adipiscing caecus dolore esca et eu lenis refoveo vereor.Causa feugiat minim oppeto pagus proprius quidne voco. Brevitas ille turpis. Olim sino vindico. Aliquam dolor persto. Decet iaceo illum jumentum neque nibh nutus ut. Genitus sit tum. Comis eu genitus huic illum ullamcorper vulputate. Abbas aliquam conventio ille jugis proprius similis sit valde. Causa conventio defui dolor erat in inhibeo meus pneum.",
    "keywords": "admin, test category",
    "directexchange": "0",
    "indirectexchange": "0",
    "money": "1",
    "scope": "4",
    "uuid": "77791de2-ce80-4d10-abfc-4130ac7ca115",
    "expires": "1498509094",
    "path": "ad/295",
    "clientId": "1",
    "lon": "0",
    "lat": "51",
    "image": "blah.jpg"
  }
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
  private $clientId;

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
  public $uuid;

  /**
   * Unixtime when the ad should be automatically delisted (either with cron, or
   * by filtering the search)
   * @var int
   */
  public $expires;

  /**
   * path to the image on the group's platform
   * @var int
   */
  public $image;

  /**
   * A POINT on the globe where the ad is from. E.g. POINT (0 50)
   * @var string
   *
   * @note is there another primitive type for this?
   */
  public $location;

  function __construct(stdClass $object, stdClass $client) {
    $this->clientId = $client->id;
    $this->name = $client->name;
    foreach(get_object_vars($object) as $name => $prop) {
      $this->{$name} = $prop;
    }
    if ($exceptions = $this->validate()) {
      throw new \Exception(implode(';', $exceptions));
    }
  }

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
    $fields = ['id', 'type', 'title', 'body', 'keywords', 'expires', 'location', 'directexchange', 'indirectexchange', 'money', 'path'];

    $exceptions = [];

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
    preg_match('/POINT ?\(-?([0-9]+) (-?[0-9]+)\)/', $this->location, $matches);
    $lon = $matches[1];
    $lat = $matches[2];
    if ($lat > 90 or $lat < -90) {
      $exceptions[] = "Latitude out of range: $lat should be -90 < 90";
    }
    if ($lon > 180 or $lon < -180) {
      $exceptions[] = "Longitude out of range. $lon should be -180 < 180";
    }
    if (!is_numeric($this->scope) or $this->scope < 3 or $this->scope > 4) {
      $exceptions[] = 'Scope out of range. should be a number from 3-4';
    }
    $limit = strtotime('+1 year');
    if ($this->expires > $limit + 86400) {//a year and a day
      // @todo move fixing stuff somewhere else
      $this->expires = strtotime('+1 year');
    }
    return $exceptions;
  }

  function setUuid() {
    $this->uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      // 32 bits for "time_low"
      mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
      // 16 bits for "time_mid"
      mt_rand( 0, 0xffff ),
      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand( 0, 0x0fff ) | 0x4000,
      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand( 0, 0x3fff ) | 0x8000,
      // 48 bits for "node"
      mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
  }

  function insert() {
    $this->setUuid();
    //list($lat, $lon) = explode(',', $ad->location);
    $location = "ST_GeomFromText('$this->location')";
    $query = "INSERT INTO ads
      (`uuid`, `id`, `type`, `title`, `body`, `keywords`, `image`, `directexchange`, `indirectexchange`, `money`, `scope`, `location`, `expires`, `path`, `client_id`, `lang`)
      VALUES ('$this->uuid', '$this->id', '$this->type', '$this->title', '$this->body', '$this->keywords', '$this->image', $this->directexchange, $this->indirectexchange, $this->money, '$this->scope', $location, '$this->expires', '$this->path', ".$this->clientId.", '$this->lang')";
    $result = dbQuery($query); // PDOStatement Object
    return (bool)$result;
  }

  function update() {
    $query = "UPDATE ads SET
      type = '$this->type',
      title = '$this->title',
      body = '$this->body',
      keywords = '$this->keywords',
      image = '$this->image',
      directexchange = $this->directexchange,
      indirectexchange = $this->indirectexchange,
      money = $this->money,
      scope = $this->scope,
      location = ST_GeomFromText('{$this->location}'),
      expires = '$this->expires',
      lang = '$this->lang'
    WHERE client_id = ".$this->clientId." AND path = '$this->path'";
    return (bool) dbQuery($query);
  }
}
