<?php
// Setup script for Customer Invoice system
include_once('../includes/functions.php');

// Check if user is logged in as admin
if (!isset($_SESSION['admin'])) {
    die('Unauthorized access');
}

echo "<h2>Setting up Customer Invoice System</h2>";

try {
    // Create customer_invoices table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS `customer_invoices` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `customer_id` int(11) NOT NULL,
      `period` enum('day','week','month') NOT NULL,
      `invoice_amount` decimal(10,2) DEFAULT NULL,
      `status` enum('to_be_invoiced','sent','paid') NOT NULL DEFAULT 'to_be_invoiced',
      `due_date` date DEFAULT NULL,
      `notes` text DEFAULT NULL,
      `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `customer_id` (`customer_id`),
      KEY `status` (`status`),
      KEY `period` (`period`),
      KEY `created_date` (`created_date`),
      CONSTRAINT `customer_invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($create_table_sql);
    echo "<p style='color: green;'>✓ Customer invoices table created successfully</p>";
    
    // Clear existing data and insert fresh dummy data
    $clear_query = "DELETE FROM customer_invoices";
    $conn->exec($clear_query);
    echo "<p style='color: blue;'>ℹ Cleared existing invoice data</p>";
    
    // Get all customers to create invoices for
    $customers_query = "SELECT id, name FROM customers";
    $stmt = $conn->prepare($customers_query);
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
    if (!empty($customers)) {
        // Create comprehensive dummy data
        $dummy_invoices = [];
        
        // Create multiple invoices per customer with different statuses and periods
        foreach ($customers as $index => $customer) {
            $customer_id = $customer->id;
            $customer_name = $customer->name;
            
            // Create 2-4 invoices per customer
            $invoice_count = rand(2, 4);
            
            for ($i = 0; $i < $invoice_count; $i++) {
                $periods = ['day', 'week', 'month'];
                $statuses = ['to_be_invoiced', 'sent', 'paid'];
                $period = $periods[array_rand($periods)];
                $status = $statuses[array_rand($statuses)];
                
                // Generate realistic invoice amounts based on period
                $base_amount = 0;
                switch ($period) {
                    case 'day':
                        $base_amount = rand(50, 300);
                        break;
                    case 'week':
                        $base_amount = rand(200, 800);
                        break;
                    case 'month':
                        $base_amount = rand(1000, 5000);
                        break;
                }
                
                // Add some variation
                $invoice_amount = $base_amount + rand(-100, 200);
                if ($invoice_amount < 50) $invoice_amount = 50;
                
                // Calculate due date based on status
                $due_date = null;
                if ($status === 'to_be_invoiced') {
                    $due_date = date('Y-m-d', strtotime('+' . rand(7, 30) . ' days'));
                } elseif ($status === 'sent') {
                    $due_date = date('Y-m-d', strtotime('+' . rand(1, 14) . ' days'));
                } else { // paid
                    $due_date = date('Y-m-d', strtotime('-' . rand(1, 30) . ' days'));
                }
                
                // Create created_date with some variation
                $created_date = date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'));
                
                $dummy_invoices[] = [
                    'customer_id' => $customer_id,
                    'period' => $period,
                    'invoice_amount' => $invoice_amount,
                    'status' => $status,
                    'due_date' => $due_date,
                    'notes' => ucfirst($period) . ' service for ' . $customer_name,
                    'created_date' => $created_date
                ];
            }
        }
        
        // Insert all dummy data
        $insert_query = "INSERT INTO customer_invoices (customer_id, period, invoice_amount, status, due_date, notes, created_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        $inserted_count = 0;
        foreach ($dummy_invoices as $invoice) {
            $stmt->execute([
                $invoice['customer_id'],
                $invoice['period'],
                $invoice['invoice_amount'],
                $invoice['status'],
                $invoice['due_date'],
                $invoice['notes'],
                $invoice['created_date']
            ]);
            $inserted_count++;
        }
        
        echo "<p style='color: green;'>✓ Successfully inserted $inserted_count dummy invoice records</p>";
        
        // Show summary of created data
        $summary_query = "
            SELECT 
                status,
                period,
                COUNT(*) as count,
                SUM(invoice_amount) as total_amount
            FROM customer_invoices 
            GROUP BY status, period 
            ORDER BY status, period
        ";
        $stmt = $conn->prepare($summary_query);
        $stmt->execute();
        $summary = $stmt->fetchAll();
        
        echo "<h3>Invoice Summary:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 10px;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>Status</th>";
        echo "<th style='padding: 8px;'>Period</th>";
        echo "<th style='padding: 8px;'>Count</th>";
        echo "<th style='padding: 8px;'>Total Amount</th>";
        echo "</tr>";
        
        foreach ($summary as $row) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . ucfirst(str_replace('_', ' ', $row->status)) . "</td>";
            echo "<td style='padding: 8px;'>" . ucfirst($row->period) . "</td>";
            echo "<td style='padding: 8px;'>" . $row->count . "</td>";
            echo "<td style='padding: 8px;'>$" . number_format($row->total_amount, 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: orange;'>⚠ No customers found. Please add some customers first.</p>";
        echo "<p><a href='add-customer.php' class='btn btn-primary'>Add Customers</a></p>";
    }
    
    echo "<p style='color: green;'><strong>Setup completed successfully!</strong></p>";
    echo "<p><a href='customer-invoice.php' class='btn btn-primary'>Go to Customer Invoice Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
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
}
.btn:hover {
    background-color: #0056b3;
}
</style>
