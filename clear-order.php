<?php
require_once 'db.php';

// Delete order information from session
if (isset($_SESSION['order'])) {
    unset($_SESSION['order']);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?> 