<?php
include_once('../includes/functions.php');

$customer_id = $_GET['customer_id'] ?? null;

if (!$customer_id) {
    echo "Error: No customer ID provided";
    exit;
}

try {
    // Get customer details with invoice settings from customers
    $query = "SELECT c.id, c.name, c.email, c.invoice_period, c.last_invoice_sent
              FROM customers c
              WHERE c.id = ?
              LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        echo "Error: Customer not found";
        exit;
    }
    
    echo "<h2>Generating Invoice for: {$customer->name}</h2>";
    
    $today = date('Y-m-d');
    $start_date = $customer->last_invoice_sent ?: $today;
    
    echo "<p>Period: {$customer->invoice_period}</p>";
    echo "<p>Last Invoice: " . ($customer->last_invoice_sent ?: 'Never') . "</p>";
    echo "<p>Start Date: {$start_date}</p>";
    echo "<p>End Date: {$today}</p>";
    
    // Get candidates for this customer
    $candidates_query = "SELECT id, name, email, service_cost, travel_cost, booked 
                        FROM candidates 
                        WHERE cus_id = ? AND booked >= ? AND booked <= ? AND status IN (4, 7, 21, 22, 52, 54, 55)
                        ORDER BY booked DESC";
    $candidates_stmt = $conn->prepare($candidates_query);
    $candidates_stmt->execute([$customer->id, $start_date, $today]);
    $candidates = $candidates_stmt->fetchAll();
    
    echo "<h3>Candidates Found: " . count($candidates) . "</h3>";
    
    if (count($candidates) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Service Cost</th><th>Travel Cost</th><th>Total</th><th>Created Date</th></tr>";
        
        $total_amount = 0;
        foreach ($candidates as $candidate) {
            $candidate_total = ($candidate->service_cost ? $candidate->service_cost : 0) + ($candidate->travel_cost ? $candidate->travel_cost : 0);
            $total_amount += $candidate_total;
            
            echo "<tr>";
            echo "<td>{$candidate->id}</td>";
            echo "<td>{$candidate->name}</td>";
            echo "<td>{$candidate->email}</td>";
            echo "<td>" . ($candidate->service_cost ? $candidate->service_cost : 0) . "</td>";
            echo "<td>" . ($candidate->travel_cost ? $candidate->travel_cost : 0) . "</td>";
            echo "<td>{$candidate_total}</td>";
            echo "<td>{$candidate->booked}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Total Amount: {$total_amount}</h3>";
        
        // Create invoice
        $invoice_query = "INSERT INTO customer_invoices (customer_id, period, invoice_amount, status, due_date, notes, created_date) 
                         VALUES (?, ?, ?, 'to_be_invoiced', ?, ?, NOW())";
        $invoice_stmt = $conn->prepare($invoice_query);
        
        $due_date = date('Y-m-d', strtotime('+30 days'));
        $notes = "Auto-generated invoice for {$customer->name} covering period from {$start_date} to {$today}. " .
                "Total candidates: " . count($candidates) . ". " .
                "Invoice period: {$customer->invoice_period}";
        
        $invoice_stmt->execute([
            $customer->id,
            $customer->invoice_period,
            $total_amount,
            $due_date,
            $notes
        ]);
        
        // Update customer's last_invoice_sent date
        $update_query = "UPDATE customers SET last_invoice_sent = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->execute([$today, $customer->id]);
        
        echo "<h3 style='color: green;'>Invoice Created Successfully!</h3>";
        echo "<p>Invoice ID: " . $conn->lastInsertId() . "</p>";
        echo "<p>Due Date: {$due_date}</p>";
        
    } else {
        echo "<h3 style='color: orange;'>No candidates found for this period</h3>";
    }
    
    echo "<br><a href='test_invoice_generation.php'>Back to Customer List</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
