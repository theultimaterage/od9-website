<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=od9_tickets', 'root', '');
    $stmt = $pdo->query('DESCRIBE od9_members');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "OD9_MEMBERS TABLE STRUCTURE:\n\n";
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
