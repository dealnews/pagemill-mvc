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

$path = $_SERVER['REQUEST_URI'] ?? '/';

// Create controller
// Note: Don't pass $_GET - let the controller's filterInput() handle it
$controller = new BlogController($path);

try {
    $controller->handleRequest();
    
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
