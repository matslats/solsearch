<?php

define('DB_NAME', 'solsearch');
define('DB_USER', 'root');
define('DB_PASS', '');

foreach ($_SERVER as $name => $value) {
  if (substr($name, 0, 5) == 'HTTP_') {
    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
  }
}
if (empty($_SERVER['HTTP_APIKEY'])) {
  die('no apikey header');
}
foreach ($_SERVER as $name => $value) {
  if (substr($name, 0, 5) == 'HTTP_') {
    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
  }
}
if (isset($_SERVER['CONTENT_TYPE'])) {
  // Not used
  $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
}
if (!isset($_SERVER['ACCEPT'])) {
  // Not used
  $headers['Accept'] = 'application/json';
}

// Set up the db
$connection = mysql_connect('localhost', DB_USER, DB_PASS);
mysql_select_db(DB_NAME, $connection);

//Authenticate using the API key.
$result = mysql_query("SELECT * FROM clients WHERE apikey = '".$headers['Apikey']."'");
$client = mysql_fetch_object($result);

$status_code = 200;
$output = '';


if (empty($client->id)) {
  $result = array(403);
}
else{
  $request = parse_url($_SERVER['REQUEST_URI']);
  if (strlen($request['path']) < 2) {
    $status_code = 404;
  }
  require 'SolSearch.php';

  @list(,$endpoint, $arg1, $arg2) = explode('/', $request['path']);
  $engine = new \SolSearch($connection, $client, $arg1);
  if($client->id == 1 && $endpoint == 'client') {
    //permit the admin functions
    switch ($_SERVER["REQUEST_METHOD"]) {
      case 'POST':
        if ($obj = solSearch_json_input('client')) {
          $apikey = $engine->insertClient($obj->name, $obj->url);
          $output = ['apikey' => $apikey];
          $status_code= 201;
        }
        else {
          $status_code = 400;
        }
        break;
      case 'PUT':
        if ($obj = solSearch_json_input('client')) {
          $engine->updateClient($arg1, $obj->name, $obj->url);
        }
        else {
          $status_code = 400;
        }
        break;
      case 'DELETE':
        if($arg1 != 1) {
          //the id is in the path
          $engine->deleteClient($arg1);
        }
        break;
      case 'GET':
        $output = $engine->listClients();
        //make this into a json structure...
    }
  }
  else{
    switch ($_SERVER["REQUEST_METHOD"]) {
      case 'GET': //it never needs to GET only one. This is always a filter
        $params = $_GET;
        $params['type'] = $endpoint;
        $sortby = @$params['sortby'];
        unset($params['dir']);
        $dir = @$params['dir'];
        unset($params['sortby']);
        $output = $engine->filter($params, $sortby, $dir);
        break;
      case 'PUT':
        $obj = solSearch_json_input('ad');
        $engine->upsert($endpoint, $obj);
      case 'DELETE':
        $engine->delete($arg2);
        break;
      case 'OPTIONS':
        return $engine->getTypes();
    }
  }
}

header('Status: '. $status_code);
header('Access-Control-Allow-Origin: "*"');
header('Content-type: application/json');

print json_encode($output, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
exit;


/**
 * Utility get REQUEST body json as an array or object
 */
function solSearch_json_input($type = 'ad') {
  $input = file_get_contents('php://input');
  $obj = json_decode($input);
  if ($type == 'client') {
    $props = ['name', 'url'];
  }
  elseif($type == 'ad') {
    $props = ['title', 'body', 'keywords', 'directexchange', 'indirectexchange', 'money', 'scope', 'uuid', 'location', 'expires', 'path'];
  }
  foreach ($props as $prop) {
    if (!isset($obj->{$prop})) {
      throw new \Exception('Property missing: '.$prop);
    }
  }
  return $obj;
}