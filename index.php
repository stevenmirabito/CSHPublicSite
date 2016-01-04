<?php

/*
 * Setup & Initialization
 */

// Load dependencies from Composer
require 'vendor/autoload.php';

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

// Create a Pimple container
$container = new \Slim\Container();

// Add the Twig service to the container
$container['twig'] = function($container) {
    $loader = new Twig_Loader_Filesystem('templates');
    return new Twig_Environment($loader, ['cache' => 'cache']);
};

// Create and configure Slim app
$app = new \Slim\App($container);

/*
 * Slim Middleware
 */

// Permanently redirect paths with a trailing slash to their non-trailing counterpart
$app->add(function (Request $request, Response $response, callable $next) {
    $uri = $request->getUri();
    $path = $uri->getPath();

    if ($path != '/' && substr($path, -1) == '/') {
        $uri = $uri->withPath(substr($path, 0, -1));
        return $response->withRedirect((string)$uri, 301);
    }

    return $next($request, $response);
});

/*
 * Slim Routes
 */

// Wildcard route for GET requests
$app->get('/[{path:.*}]', function ($request, $response, $args) {
    // Load the template for the requested page
    $template = $this->twig->loadTemplate('index.twig');

    // Render the template and return it to the app
    return $response->write($template->render(['name' => 'index']));
    //return $response->write("Hi!");
});

$app->post('/contact', function ($request, $response, $args) {
    return $response->write("Hello! You said you wanted to go to: contact");
});

// Run Slim app
$app->run();