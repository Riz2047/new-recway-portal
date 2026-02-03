<?php
include_once('../includes/functions.php');

try {
    $conn->exec("ALTER TABLE customer_invoices ADD COLUMN candidate_ids MEDIUMTEXT NULL");
    echo "Added candidate_ids column to customer_invoices\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "candidate_ids already exists\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Ensure invoice_genrated flag exists on candidates
try {
    $conn->exec("ALTER TABLE candidates ADD COLUMN invoice_genrated TINYINT(1) NOT NULL DEFAULT 0");
    echo "Added invoice_genrated column to candidates\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "invoice_genrated already exists\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
?>


