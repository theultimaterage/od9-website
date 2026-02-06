<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=od9_tickets', 'root', '');
    
    // Add the missing pioneer_completed column
    $sql = "ALTER TABLE od9_members 
            ADD COLUMN pioneer_completed TINYINT(1) DEFAULT 0 
            AFTER architect_completed";
    
    $pdo->exec($sql);
    
    echo "SUCCESS: Added pioneer_completed column to od9_members table\n";
    
    // Verify it was added
    $stmt = $pdo->query("DESCRIBE od9_members");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('pioneer_completed', $columns)) {
        echo "VERIFIED: Column exists in table\n";
    } else {
        echo "ERROR: Column was not added\n";
    }
    
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
