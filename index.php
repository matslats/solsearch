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

if ($client->id == 1) {
  require 'SolSearchAdmin.php';
  $solSearch = new \SolSearchAdmin($dbh, $logFileHandle, $client);
}
else {
  require 'SolSearch.php';
  $solSearch = new SolSearch($dbh, $logFileHandle, $client);
}
// Instantiate the SolSearch service


$container = $app->getContainer() ;
$container['solSearch'] = $solSearch ;

// Search for ads
$app->get('/search', function (Request $request, Response $response, $args) {
  $params = $request->getQueryParams();
  $type = $params['type'];
  $sortby = isset($params['sortby']) ? $params['sortby'] : 'expires,DESC';
  $offset = @$params['offset'];
  $limit = @$params['limit'];
  unset($params['type'], $params['sortby'], $params['dir'], $params['limit'], $params['offset']);
  return $response->withJson($this->get('solSearch')->searchAds($type, $params, $offset, $limit, $sortby));
});

// Get one ad
$app->get('/ad/{adId}', function (Request $request, Response $response, $args) {
   return $response->withJson($this->get('solSearch')->getAd($args['adId'])) ;
});


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
