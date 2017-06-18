<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require 'SolSearch.php';

// Instatiate PDO database connection

$dbConfig = parse_ini_file('db.ini');
$dbh = connectPdo($dbConfig);

// Instantiate the SolSearch service

$solSearch = new SolSearch($dbh, "", "");

// Define API

$app = new \Slim\App;

// READING

// Clients Endpoint
// If client ID specified, then get just that client, otherwise return all clients
$app->get('/clients[/{clientId}]', function (Request $request, Response $response, $args) {
    if (isset($args['clientId'])) {
        return $response->withJson(array('name' => 'yes', 'foo' => 40));
    } else {
        return $response->withJson(array('name' => 'no', 'foo' => 40));
    }
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
