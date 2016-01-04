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
$container['twig'] = function ($container) {
    $loader = new Twig_Loader_Filesystem('templates');
    return new Twig_Environment($loader, ['cache' => 'cache']);
};

// Add our custom error handler to the container
$container['errorHandler'] = function ($container) {
    return function ($request, $response, $exception) use ($container) {
        // Load the error page template
        $template = $container['twig']->loadTemplate('error.twig');
        return $container['response']
            ->withStatus(500)
            ->withHeader('Content-Type', 'text/html')
            ->write($template->render(['code' => 500]));
    };
};

// Add our custom not found handler to the container
$container['notFoundHandler'] = function ($container) {
    return function ($request, $response) use ($container) {
        // Load the error page template
        $template = $container['twig']->loadTemplate('error.twig');
        return $container['response']
            ->withStatus(404)
            ->withHeader('Content-Type', 'text/html')
            ->write($template->render(['code' => 404]));
    };
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

// Capture 404 and 500 responses and redirect them to our custom error handlers
$app->add(function ($request, $response, $next) use ($container) {
    // First execute anything else
    $response = $next($request, $response);

    // Check if the response should be handled by one of our custom error handlers
    if (!$response->getBody()->getSize()) {
        if ($response->getStatusCode() === 404) {
            // Pass the request to the notFoundHandler
            return $container['notFoundHandler']($request, $response);
        } else if ($response->getStatusCode() === 500) {
            // Pass the request to the errorHandler
            return $container['errorHandler']($request, $response);
        }
    }

    // Pass on current response for any other request
    return $response;
});

/*
 * Slim Routes
 */

// Wildcard route for GET requests (allows alphanumeric, forward slash, dash, and underscore)
$app->get('/[{path:[a-zA-Z0-9\/\-\_]+}]', function ($request, $response, $args) {
    // If $path isn't set, set it to 'index' to load the homepage
    if (!isset($args['path'])) {
        $args['path'] = 'index';
    }

    // See if there's a template for the path requested
    if (file_exists(sprintf("templates/%s.twig", $args['path']))) {
        // Load the template for the requested page
        $template = $this->twig->loadTemplate(sprintf("%s.twig", $args['path']));
        return $response->write($template->render(['name' => basename($args['path'])]));
    } else {
        // Return a 404
        return $response->withStatus(404);
    }
});

$app->post('/contact', function ($request, $response, $args) {
    return $response->write("Hello! You said you wanted to go to: contact");
});

// Run Slim app
$app->run();