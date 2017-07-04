<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require 'SolSearch.php';

// Instatiate PDO database connection

$config = parse_ini_file('config.ini', true);
$dbh = connectPdo($config['db']);

$logFileHandle = fopen($config['logging']['logfile'], 'a');

$app = new \Slim\App();

// Instantiate the SolSearch service
$solSearch = new SolSearch($dbh, $logFileHandle);

$container = $app->getContainer() ;
$container['solSearch'] = $solSearch ; 
  
// Get one ad
$app->get('/ad/{adId}', function (Request $request, Response $response, $args) {
   return $response->withJson($this->get('solSearch')->getAd($args['adId'])) ; 
});

// Search for ads
$app->get('/searchAd', function (Request $request, Response $response, $args) {
   $params = $request->getQueryParams();
   $type = $params['type'];
   return $response->withJson($this->get('solSearch')->searchAds($type));
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
