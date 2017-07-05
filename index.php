<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
// Instatiate PDO database connection

$config = parse_ini_file('config.ini', true);
$dbh = connectPdo($config['db']);

$logFileHandle = fopen($config['logging']['logfile'], 'a');
$result = $dbh->query("SELECT * FROM clients WHERE apikey = '".$_SERVER['HTTP_APIKEY']."'");

$client = $result->fetchObject();
$app = new \Slim\App();

$container = $app->getContainer() ;

if ($client->id == 1) {
  require 'SolSearchAdmin.php';
  $container['solSearch'] = new \SolSearchAdmin($dbh, $logFileHandle, $client);
}
else {
  require 'SolSearch.php';
  $container['solSearch'] = new SolSearch($dbh, $logFileHandle, $client);
}
// Instantiate the SolSearch service



// Search for ads
$app->get('/ads', function (Request $request, Response $response, $args) {
  $params = $request->getQueryParams();
  $type = $params['type'];
  $sortby = isset($params['sortby']) ? $params['sortby'] : 'expires,DESC';
  $offset = @$params['offset'];
  $limit = @$params['limit'];
  unset($params['type'], $params['sortby'], $params['dir'], $params['limit'], $params['offset']);
  return $response->withJson($this->get('solSearch')->searchAds($type, $params, $offset, $limit, $sortby));
});

// Get one ad
$app->get('/ads/{uuid}', function (Request $request, Response $response, $args) {
  return $response->withJson($this->get('solSearch')->getAd($args['uuid']));
});

// Delete one ad
$app->delete('/ads/{uuid}', function (Request $request, Response $response, $args) {
  return $response->withJson($this->get('solSearch')->deleteAd($args['uuid']));
});

// Update one ad
$app->put('/ads', function (Request $request, Response $response, $args) {
  $data = json_decode($request->getBody());
  $solAd = new SolAd($data);
  return $response->withJson($this->get('solSearch')->updateAd($solAd));
});

// Bulk create/update
$app->put('/bulk', function (Request $request, Response $response, $args) {
  $solAds = [];
  $data = json_decode($request->getBody());
  foreach($data as $obj) {
    $solAds[] = new SolAd($obj);
  }
  return $response->withJson($this->get('solSearch')->bulkUpdateAds($solAds));
});

// Bulk delete
$app->delete('/bulk', function (Request $request, Response $response, $args) {
  $uuids = json_decode($request->getBody());
  return $response->withJson($this->get('solSearch')->bulkDeleteAds($uuids));
});


// Create one ad
$app->post('/ads', function (Request $request, Response $response, $args) {
  $data = json_decode($request->getBody());
  $solAd = new SolAd($data);
  return $response->withJson($this->get('solSearch')->insertAd($solAd));
});

// Delete a group and all its ads
$app->delete('/groups', function (Request $request, Response $response, $args) {
  return $response->withJson($this->get('solSearch')->deleteGroup());
});

/**
 * Admin only operations
 */
// Create a new group, returning the apikey
$app->post('/groups', function (Request $request, Response $response, $args) {
  $data = json_decode($request->getBody());
  // @todo Need to validate this data
  return $response->withJson($this->get('solSearch')->insertGroup($data));
});

// Delete a group and all its ads
$app->get('/groups', function (Request $request, Response $response, $args) {
  return $response->withJson($this->get('solSearch')->listGroups());
});

// Update a group - no use-case for this right now.
//$app->put('/group', function (Request $request, Response $response, $args) {
//  $data = json_decode($request->getBody());
//  // @todo Need to validate this data
//  return $response->withJson($this->get('solSearch')->updateGroup($data));
//});







// Start API
$app->run();



function connectPdo($dbConfig) {
    $dsn = 'mysql:dbname=' . $dbConfig['name'] . ';host=' . $dbConfig['host'].';' ;
    try {                                                                                                                                   $dbh = new PDO($dsn, $dbConfig['username'], $dbConfig['password']) ;                                                            } catch (PDOException $e) {
        echo 'PDO Connection failed: ' . $e->getMessage();
        exit(1);
    }
    return $dbh;
}
