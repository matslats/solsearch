<?php

define('DB_NAME', 'solsearch');
define('DB_USER', 'root');
define('DB_PASS', '');


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
$connection = mysql_connect('localhost', DB_USER, DB_PASS, $new_link);
mysql_select_db(DB_NAME, $connection);

//Authenticate using the API key.
$result = mysql_query("SELECT name, url WHERE apikey = '".$headers['apikey']."'", $link_identifier);
list($name, $url) = mysql_fetch_object($result);
$status_code = 200;
$output = '';

if (empty($name)) {
  $result = array(403);
}
else{
  $request = parse_url($_SERVER['REQUEST_URI']);
  @list($resource_type, $id) = explode('/', $request['path']);
  $engine = new SolSearch($headers['apikey'], $connection);
  if($name == 'admin' && $endpoint = 'client') {
    //permit the admin functions
    switch ($_SERVER["REQUEST_METHOD"]) {
      case 'POST':
      case 'PUT':
        $obj = solSearch_json_input();
        $engine->upsertClient($headers['apikey'],  $obj->url, $obj->name);
        break;
      case 'DELETE':
        //the uuid is in the path
        $engine->deleteClient();
        break;
      case 'GET':
        $output = $engine->listClients();
        //make this into a json structure...
    }
  }
  elseif ($endpoint = 'ad') {
    switch ($_SERVER["REQUEST_METHOD"]) {
      case 'GET': //it never needs to GET only one. This is always a filter
        $params = $_GET;
        $sortby = @$params['sortby'];
        unset($params['dir']);
        $sortby = @$params['dir'];
        unset($params['sortby']);
        $output = $engine->listClients($params, $sortby, $dir);
        break;
      case 'POST':
      case 'PUT':
        $obj = solSearch_json_input();
        $engine->upsert($obj);
        break;
      case 'DELETE':
        $engine->delete($uuid);
        break;

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
function solSearch_json_input() {
  $input = file_get_contents('php://input');
  return (array)json_decode($input);
}