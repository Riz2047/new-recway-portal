<?php
// Simple script to create the customer_invoices table
include_once('../includes/functions.php');

// Check if user is logged in as admin
if (!isset($_SESSION['admin'])) {
    die('Unauthorized access');
}

echo "<h2>Creating Customer Invoices Table</h2>";

try {
    // Create customer_invoices table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS `customer_invoices` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `customer_id` int(11) NOT NULL,
      `invoice_amount` decimal(10,2) DEFAULT NULL,
      `status` enum('to_be_invoiced','sent','paid') NOT NULL DEFAULT 'to_be_invoiced',
      `notes` text DEFAULT NULL,
      `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `customer_id` (`customer_id`),
      KEY `status` (`status`),
      KEY `period` (`period`),
      KEY `created_date` (`created_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($create_table_sql);
    echo "<p style='color: green;'>✓ Customer invoices table created successfully</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h2 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
p {
    margin: 10px 0;
    padding: 5px;
    border-radius: 3px;
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 20px;
    margin-right: 10px;
}
.btn-secondary {
    background-color: #6c757d;
}
.btn:hover {
    opacity: 0.8;
}
</style>
