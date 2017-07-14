<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\App;


set_exception_handler('solsearch_exception');
require 'vendor/autoload.php';
// Instatiate PDO database connection

$config = parse_ini_file('config.ini', true);
global $dbh, $logFileHandle;
$dbh = connectPdo($config['db']);

$logFileHandle = fopen($config['logging']['logfile'], 'a');

$client = $dbh->query("SELECT * FROM clients WHERE apikey = '".$_SERVER['HTTP_APIKEY']."'")->fetchObject();
$app = new App();

if (empty($client)) {
  throw new \Exception('unknown client');
}
require 'SolAd.php';
$container = $app->getContainer() ;

if ($client->id == 1) {
  require 'SolSearchAdmin.php';
  $container['solSearch'] = new \SolSearchAdmin($client);
}
else {
  require 'SolSearch.php';
  $container['solSearch'] = new SolSearch($client);
}
// Instantiate the SolSearch service using the Request path


// Search for ads
$app->get('/ads', function (Request $request, Response $response, $args) {
  $params = $request->getQueryParams();
  if (!isset($params['type'])){
    trigger_error("Missing required url parameter 'type'", E_USER_ERROR);
  }
  $type = $params['type'];
  $sortby = isset($params['sortby']) ? $params['sortby'] : 'expires,DESC';
  $offset = @$params['offset'];
  $limit = @$params['limit'];
  unset($params['type'], $params['sortby'], $params['dir'], $params['limit'], $params['offset']);

  return $response->withJson($this->get('solSearch')->searchAds($type, $params, $offset, $limit, $sortby));
});

// Get one ad
$app->get('/ads/{id}', function (Request $request, Response $response, $args) {
  return $response->withJson($this->get('solSearch')->getAd($args['id']));
});

// Delete one ad
$app->delete('/ads/{id}', function (Request $request, Response $response, $args) {
  return $response->withJson($this->get('solSearch')->deleteAd($args['id']));
});

// Update one ad
$app->put('/ads', function (Request $request, Response $response, $args) {
  global $client;
  $data = json_decode($request->getBody());
  $solAd = new SolAd($data, $client);
  if ($this->get('solSearch')->exists($solAd->id)) {
    return $response->withJson($this->get('solSearch')->updateAd($solAd));
  }
  else {
    return $response->withJson($this->get('solSearch')->insertAd($solAd));
  }
});
// Create one ad
$app->post('/ads', function (Request $request, Response $response, $args) {
  global $client;
  $data = json_decode($request->getBody());
  $solAd = new SolAd($data, $client);
  return $response->withJson($this->get('solSearch')->insertAd($solAd));
});


// Bulk create/update
$app->put('/bulk', function (Request $request, Response $response, $args) {
  global $client;
  $solAds = [];
  $data = json_decode($request->getBody());
  foreach($data as $obj) {
    $solAds[] = new SolAd($obj, $client);
  }
  return $response->withJson($this->get('solSearch')->bulkUpdateAds($solAds));
});

// Bulk delete
$app->delete('/bulk', function (Request $request, Response $response, $args) {
  $ids = json_decode($request->getBody());
  return $response->withJson($this->get('solSearch')->bulkDeleteAds($ids));
});



// Delete the current group and all its ads
$app->delete('/groups', function (Request $request, Response $response, $args) {
  return $response->withJson($this->get('solSearch')->bye());
});


/**
 * Admin only operations
 */
// Create a new group, returning the apikey
$app->post('/groups', function (Request $request, Response $response, $args) {
  $data = json_decode($request->getBody());
  // @todo Need to validate this data
  return $response->withJson($this->get('solSearch')->insertGroup($data->url, $data->name));
});

// Get a list of groups
$app->get('/groups', function (Request $request, Response $response, $args) {
  return $response->withJson($this->get('solSearch')->listGroups());
});

// Delete the given group and all its ads
$app->delete('/groups/{id}', function (Request $request, Response $response, $args) {
  return $response->withJson($this->get('solSearch')->deleteGroup());
});

// Update a group - no use-case for this right now.
//$app->put('/group', function (Request $request, Response $response, $args) {
//  $data = json_decode($request->getBody());
//  // @todo Need to validate this data
//  return $response->withJson($this->get('solSearch')->updateGroup($data));
//});



// Start API and return results
$app->run();



function connectPdo($dbConfig) {
  $dsn = 'mysql:dbname=' . $dbConfig['name'] . ';host=' . $dbConfig['host'].';' ;
  try {                                                                                                                                   $dbh = new PDO($dsn, $dbConfig['username'], $dbConfig['password']) ;                                                            } catch (PDOException $e) {
    echo 'PDO Connection failed: ' . $e->getMessage();
    exit(1);
  }
  return $dbh;
}

function dbQuery($sqlString, $log = TRUE) {
  try {
    global $dbh;
    if ($log) {
      solsearch_log($sqlString);
    }
    return $dbh->query($sqlString);
  }
  // @todo  does this need to be logged to file?
  catch(PDOException $e) {
    solsearch_log('Error: ', $e->getMessage());
    throw new \Exception('Error in query: '.$sqlString);
  }
}

function solsearch_log($message) {
  global $logFileHandle, $client;
  if (!is_string($message)) {
    print_r(array_slice(debug_backtrace(), 0, 3));
  }
  dbQuery("INSERT INTO log (message, client_id) VALUES ('$message', '$client->id')", 0);
  fputs($logFileHandle, $message . "\n");

}

function solsearch_exception($message) {
  // Todo stop this returning a 200 OK code. Maybe using a custom exception handler
  $newResponse = new Slim\Http\Response(400);
  $newResponse->write($message);
  $app->respond($newResponse);//not sure if this works
  exit;
}