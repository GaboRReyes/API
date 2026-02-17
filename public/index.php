<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../core/Router.php';
require_once '../resources/v1/UserResource.php';
require_once '../resources/v1/ProductResource.php';
require_once '../resources/v1/AuthResource.php'; 

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath  = ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') ? '' : $scriptDir;

$router       = new Router('v1', $basePath);
$authResource = new AuthResource();
$userResource = new UserResource();
$productResource = new ProductResource();


// user routes
$router->addRoute('GET', '/users', [$userResource, 'index']);
$router->addRoute('GET', '/users/{id}', [$userResource, 'show']);
$router->addRoute('POST', '/users', [$userResource, 'store']);
$router->addRoute('PUT', '/users/{id}', [$userResource, 'update']);
$router->addRoute('DELETE', '/users/{id}', [$userResource, 'destroy']);

// product routes
$router->addRoute('GET', '/products', [$productResource, 'index']);
$router->addRoute('GET', '/products/{id}', [$productResource, 'show']);
$router->addRoute('POST', '/products', [$productResource, 'store']);
$router->addRoute('PUT', '/products/{id}', [$productResource, 'update']);
$router->addRoute('DELETE', '/products/{id}', [$productResource, 'destroy']);

//login route
$router->addRoute('POST', '/auth/login',  [$authResource, 'login']);
$router->addRoute('POST', '/auth/logout', [$authResource, 'logout']);

$router->dispatch();
?>
