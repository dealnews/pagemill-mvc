<?php
/**
 * Blog API Entry Point
 *
 * JSON API endpoint that returns blog posts.
 * Demonstrates API-only responses without content negotiation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use BlogExample\Controllers\BlogController;
use PageMill\HTTP\Response;

// Force JSON response
header('Content-Type: application/json');

$response = new Response();

$path = $_SERVER['REQUEST_URI'] ?? '/';
$inputs = $_GET;

$controller = new BlogController($path, $inputs);

try {
    $controller->handleRequest();
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
