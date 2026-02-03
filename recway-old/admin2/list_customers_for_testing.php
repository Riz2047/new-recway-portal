<?php
include_once('../includes/functions.php');

echo "<h2>Available Customers for Testing</h2>";
echo "<p>Here are the customers you can test with:</p>";

try {
    // Get all customers
    $query = "SELECT c.id, c.name, c.email, ci.invoice_period, ci.last_invoice_sent, ci.id as invoice_id
              FROM customers c
              LEFT JOIN customer_invoices ci ON c.id = ci.customer_id
              ORDER BY c.id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Customer ID</th><th>Name</th><th>Email</th><th>Invoice Period</th><th>Last Invoice</th><th>Test Link</th></tr>";
    
    foreach ($customers as $customer) {
        $test_link = "generate_single_invoice.php?customer_id=" . $customer->id;
        echo "<tr>";
        echo "<td>{$customer->id}</td>";
        echo "<td>{$customer->name}</td>";
        echo "<td>{$customer->email}</td>";
        echo "<td>" . ($customer->invoice_period ?: 'Not set') . "</td>";
        echo "<td>" . ($customer->last_invoice_sent ?: 'Never') . "</td>";
        echo "<td><a href='{$test_link}' target='_blank'>Test Invoice Generation</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>Quick Test Links:</h3>";
    foreach ($customers as $customer) {
        echo "<p><a href='generate_single_invoice.php?customer_id={$customer->id}' target='_blank'>Test Customer: {$customer->name} (ID: {$customer->id})</a></p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
