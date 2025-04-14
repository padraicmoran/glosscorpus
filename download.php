<?php
// Configuration
$baseDir = realpath(__DIR__ . '/../../data'); // Base directory for XML files

// Check and sanitize 'p' parameter
if (!isset($_GET['p'])) {
    http_response_code(400);
    echo "No file path.";
    exit;
}

$requestedPath = $_GET['p'];

// Normalize and sanitize path to prevent directory traversal
$fullPath = realpath($baseDir . '/' . $requestedPath);

// Security checks
if (
    $fullPath === false ||                     // File doesn't exist
    strpos($fullPath, $baseDir) !== 0 ||       // Escaped base directory
    pathinfo($fullPath, PATHINFO_EXTENSION) !== 'xml' || // Not an XML file
    !is_file($fullPath)                        // Not a regular file
) {
    http_response_code(404);
    echo "XML file not found or access denied.";
    exit;
}

// Serve the XML file
header('Content-Type: application/xml; charset=utf-8');
readfile($fullPath);
?>