<?php
include_once('../includes/functions.php');

try {
    echo "Restoring invoice fields to customers...\n";

    // Add back to customers
    $conn->exec("ALTER TABLE customers ADD COLUMN invoice_period ENUM('day','week','month') DEFAULT 'month'");
    echo "Added customers.invoice_period\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "customers.invoice_period already exists\n";
    } else { echo $e->getMessage()."\n"; }
}

try {
    $conn->exec("ALTER TABLE customers ADD COLUMN last_invoice_sent DATE NULL");
    echo "Added customers.last_invoice_sent\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "customers.last_invoice_sent already exists\n";
    } else { echo $e->getMessage()."\n"; }
}

// Indexes
try { $conn->exec("ALTER TABLE customers ADD INDEX idx_invoice_period (invoice_period)"); } catch (Exception $e) {}
try { $conn->exec("ALTER TABLE customers ADD INDEX idx_last_invoice_sent (last_invoice_sent)"); } catch (Exception $e) {}

// Drop from customer_invoices if present
try { $conn->exec("ALTER TABLE customer_invoices DROP COLUMN invoice_period"); echo "Dropped customer_invoices.invoice_period\n"; } catch (Exception $e) {}
try { $conn->exec("ALTER TABLE customer_invoices DROP COLUMN last_invoice_sent"); echo "Dropped customer_invoices.last_invoice_sent\n"; } catch (Exception $e) {}

echo "Done.\n";
?>


