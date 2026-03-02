<?php
/**
 * Blog Application Entry Point
 *
 * This is the main entry point for the HTML interface.
 * It handles both listing and single post views.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use BlogExample\Controllers\BlogController;
use PageMill\HTTP\Response;

// Create HTTP response
$response = new Response();

// Determine path (for demo, we'll use a simple scheme)
$path = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($path, PHP_URL_PATH);

// Get query parameters
$inputs = $_GET;

// Create and execute controller
$controller = new BlogController($path, $inputs);

try {
    $controller->handleRequest();
    
} catch (\Exception $e) {
    // Simple error handling for demo
    http_response_code(500);
    echo '<h1>Error</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    
    if (\PageMill\MVC\Environment::debug()) {
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
}
