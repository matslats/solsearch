<?php
require 'conf.php';

header('Content-type: application/json');


foreach ($_SERVER as $name => $value) {
  if (substr($name, 0, 5) == 'HTTP_') {
    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
  }
}
if (empty($_SERVER['HTTP_APIKEY'])) {
  throw new \Exception('no apikey header');
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

set_exception_handler('solsearch_exception_handler');
//Authenticate using the API key.
$result = mysql_query("SELECT * FROM clients WHERE apikey = '".$headers['Apikey']."'");
$client = mysql_fetch_object($result);

$status_code = 200;
$output = '';


$request = parse_url($_SERVER['REQUEST_URI']);
@list(,$endpoint, $arg1) = explode('/', $request['path']);
global $engine;
if ($client->id == 1) {
  require 'SolSearchAdmin.php';
  $engine = new \SolSearchAdmin($connection, $client, $arg1);
}
else {
  require 'SolSearch.php';
  $engine = new \SolSearch($connection, $client, $arg1);
}
if (empty($client->id)) {
  $engine->log("Unidentified client");
  $result = array(403);
}
else{
  if (strlen($request['path']) < 2) {
    $status_code = 404;
  }

  if($client->id == 1 && $endpoint == 'client') {
    //permit the admin functions
    switch (strtoupper($_SERVER["REQUEST_METHOD"])) {
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
    switch (strtoupper($_SERVER["REQUEST_METHOD"])) {
      case 'GET': //it never needs to GET only one. This is always a filter
        $params = $_GET;
        $sortby = isset($params['sortby']) ? $params['sortby'] : 'expires,DESC';
        $offset = @$params['offset'];
        $limit = @$params['limit'];
        unset($params['sortby'], $params['dir'], $params['limit'], $params['offset']);
        $output = $engine->filter($endpoint, $params, $offset, $limit, $sortby);
        break;
      case 'POST':
      case 'PUT':
        if ($arg1 == 'bulk') {
          $items = solSearch_json_input();
        }
        else {
          $items = [solSearch_json_input()];
        }
        if ($success = $engine->upsert($endpoint, $items)) {
          // Assuming no problems
          $status_code= 201;
        }
        break;
      case 'DELETE':
        $uuids = $arg1 ? [$arg1] : solSearch_json_input();
        $engine->delete($uuids);
        break;
    }
  }
}

header('Status: '. $status_code);
header('Access-Control-Allow-Origin: "*"');

print json_encode($output, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
exit;


/**
 * Utility get REQUEST body json as an array or object
 *
 * @return mixed
 *   The request body, converted from json
 */
function solSearch_json_input() {
  $input = file_get_contents('php://input');
  $result = json_decode($input);
  return $result;
}

/**
 * Custom exception handler callback
 *
 * @param Exception $exception
 */
function solsearch_exception_handler(\Exception $exception) {
  global $engine;
  header('Status: 400');
  header('Content-type: application/json');
  $message= $exception->getMessage();
  file_put_contents('error.log', $message);
  $engine->log($message);
  exit;
}