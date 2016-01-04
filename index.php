<?php
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/*
 * Setup & Initialization
 */

// Load dependencies from Composer
require 'vendor/autoload.php';

// Create and configure Slim app
$app = new \Slim\App;

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

// We only need one wildcard route for GET requests
$app->get('/[{path:.*}]', function ($request, $response, $args) {
    // Create and configure Twig
    $loader = new Twig_Loader_Filesystem('templates');
    $twig = new Twig_Environment($loader, array(
        'cache' => false
    ));

    // Load the template for the requested page
    $template = $twig->loadTemplate('index.twig');

    // Render the template and return it to the app
    return $response->write($template->render(array('name' => 'index')));
});

$app->post('/contact', function ($request, $response, $args) {
    return $response->write("Hello! You said you wanted to go to: contact");
});

// Run Slim app
$app->run();