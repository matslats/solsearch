<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

// Instatiate PDO database connection

$dbConfig = parse_ini_file('db.ini');
$dbh = connectPdo($dbConfig);

// Instantiate the SolSearch service

$solSearch = new SolSearch($dbh, "", "");


// Define API

$app = new \Slim\App;
$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");

    return $response;
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
