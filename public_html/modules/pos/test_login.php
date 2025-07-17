<?php
session_start();

// Simple test login for POS testing
require_once '../../../includes/db.php';

echo "<h3>POS Test Login</h3>\n";

// Check if there are users in the database
$stmt = $conn->prepare("SELECT id, nombre, correo, rol FROM usuarios WHERE rol IN ('vendedor', 'admin', 'jefe') LIMIT 5");
$stmt->execute();
$users = $stmt->fetchAll();

echo "Available users:\n";
foreach ($users as $user) {
    echo "- ID: {$user['id']}, Name: {$user['nombre']}, Email: {$user['correo']}, Role: {$user['rol']}\n";
}

// If user_id is provided, log them in
if (isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_email'] = $user['correo'];
        $_SESSION['user_role'] = $user['rol'];
        
        echo "\n✅ Logged in as: {$user['nombre']} ({$user['rol']})\n";
        echo "\nSession data:\n";
        print_r($_SESSION);
        
        echo "\n<a href='../index.php'>Go to POS System</a>\n";
    } else {
        echo "\n❌ User not found\n";
    }
} else {
    echo "\n\nTo login, add ?user_id=X to the URL (where X is the user ID)\n";
    if (count($users) > 0) {
        echo "Example: ?user_id={$users[0]['id']}\n";
    }
}
?>
