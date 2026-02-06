<?php
require 'C:/xampp/htdocs/shared-platform/bootstrap.php';

Database::setCurrentSite('od9');

$stmt = db()->prepare('SELECT id, email, username, role FROM admins WHERE email = ?');
$stmt->execute(['admin@offda9.com']);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    echo "Admin found:\n";
    echo json_encode($admin, JSON_PRETTY_PRINT);
} else {
    echo "Admin not found in od9_tickets database\n";
}
