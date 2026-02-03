<?php
include_once('../includes/functions.php');

try {
    $conn->exec("ALTER TABLE customer_invoices ADD COLUMN period ENUM('day','week','month') NULL");
    echo "Added period column to customer_invoices\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "period column already exists on customer_invoices\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
?>


