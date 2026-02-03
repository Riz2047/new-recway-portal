<?php
// Test script to verify customer invoice data fetching
include_once('includes/header.php');

echo "<h2>Customer Invoice Data Test</h2>";

try {
    // Test the query that will be used in the invoice dashboard
    $query = "SELECT 
                c.id,
                c.name as customer_name,
                'month' as period,
                '0.00' as invoice_amount,
                'to_be_invoiced' as status,
                DATE_ADD(c.created_at, INTERVAL 30 DAY) as due_date,
                c.created_at as created_date,
                '' as notes
              FROM customers c
              ORDER BY c.created_at DESC
              LIMIT 10";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
    echo "<p style='color: green;'>✓ Successfully fetched " . count($customers) . " customers</p>";
    
    if (!empty($customers)) {
        echo "<h3>Sample Customer Data:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>ID</th>";
        echo "<th style='padding: 8px;'>Customer Name</th>";
        echo "<th style='padding: 8px;'>Period</th>";
        echo "<th style='padding: 8px;'>Status</th>";
        echo "<th style='padding: 8px;'>Due Date</th>";
        echo "<th style='padding: 8px;'>Created Date</th>";
        echo "</tr>";
        
        foreach ($customers as $customer) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($customer->id) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($customer->customer_name) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($customer->period) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($customer->status) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($customer->due_date) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($customer->created_date) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No customers found in the database</p>";
    }
    
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
h3 {
    color: #555;
    margin-top: 20px;
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
table {
    margin-top: 15px;
}
</style>
