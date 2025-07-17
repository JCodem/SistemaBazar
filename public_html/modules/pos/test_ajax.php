<?php
// Test AJAX endpoint without middleware
header('Content-Type: application/json');

// Basic test
echo json_encode([
    'success' => true,
    'message' => 'Test endpoint working',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
