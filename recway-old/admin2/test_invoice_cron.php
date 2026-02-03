<?php
include_once('../includes/functions.php');

// Simple test harness to set last_invoice_sent and run the cron, then show results

$message = '';
$cronOutput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $last_invoice_sent = !empty($_POST['last_invoice_sent']) ? $_POST['last_invoice_sent'] : null;
    $invoice_period = !empty($_POST['invoice_period']) ? $_POST['invoice_period'] : 'month';

    if ($customer_id > 0) {
        // Update customer invoice settings
        $stmt = $conn->prepare("UPDATE customers SET invoice_period = ?, last_invoice_sent = ? WHERE id = ?");
        $stmt->execute([$invoice_period, $last_invoice_sent, $customer_id]);
        $message = 'Updated customer ' . $customer_id . ' with period=' . $invoice_period . ' and last_invoice_sent=' . ($last_invoice_sent ?: 'NULL');

        // Run cron and capture output
        ob_start();
        include __DIR__ . '/cron_generate_invoices.php';
        $cronOutput = nl2br(ob_get_clean());
    } else {
        $message = 'Please select a valid customer.';
    }
}

// Load customers for dropdown
$customers = findAllByQuery('SELECT id, name, email FROM customers ORDER BY id DESC');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Test Invoice Cron</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        label { display:block; margin: 8px 0 4px; }
        select, input { padding: 6px; width: 320px; }
        button { padding: 8px 14px; margin-top: 12px; }
        .box { border:1px solid #ddd; padding:16px; border-radius:6px; margin-bottom:20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f7f7f7; }
        .muted { color:#666; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
    </head>
<body>
    <h2>Test: Generate Invoices via Cron</h2>

    <?php if (!empty($message)) { ?>
        <div class="box"><?php echo htmlspecialchars($message); ?></div>
    <?php } ?>

    <form method="post" class="box">
        <label for="customer_id">Customer</label>
        <select name="customer_id" id="customer_id" required>
            <option value="">-- Select Customer --</option>
            <?php foreach ($customers as $c) { ?>
                <option value="<?php echo $c->id; ?>"><?php echo '#' . $c->id . ' - ' . htmlspecialchars($c->name) . ' (' . htmlspecialchars($c->email) . ')'; ?></option>
            <?php } ?>
        </select>

        <label for="invoice_period">Invoice Period</label>
        <select name="invoice_period" id="invoice_period">
            <option value="day" selected>Daily</option>
            <option value="week">Weekly</option>
            <option value="month">Monthly</option>
        </select>

        <label for="last_invoice_sent">Last Invoice Sent (YYYY-MM-DD)</label>
        <input type="date" name="last_invoice_sent" id="last_invoice_sent" value="<?php echo date('Y-m-d', strtotime('-2 days')); ?>" />
        
        <div style="margin: 10px 0; padding: 10px; background: #f0f8ff; border-radius: 4px;">
            <strong>Daily Test Setup:</strong><br>
            • Set period to "Daily"<br>
            • Set last invoice sent to 2 days ago<br>
            • This will generate invoices for candidates booked in the last 2 days
        </div>

        <button type="submit">Run Cron</button>
    </form>

    <?php if (!empty($cronOutput)) { ?>
        <div class="box">
            <h3>Cron Output</h3>
            <div class="muted">(from cron_generate_invoices.php)</div>
            <div><?php echo $cronOutput; ?></div>
        </div>
    <?php } ?>

    <?php if (!empty($_POST['customer_id'])) {
        $cid = intval($_POST['customer_id']);
        $invoices = findAllByQuery("SELECT ci.id, ci.customer_id, c.invoice_period AS period, ci.invoice_amount, ci.status, ci.created_date, ci.candidate_ids FROM customer_invoices ci LEFT JOIN customers c ON ci.customer_id = c.id WHERE ci.customer_id = '$cid' ORDER BY ci.created_date DESC LIMIT 10");
    ?>
        <div class="box">
            <h3>Latest Invoices for Customer #<?php echo $cid; ?></h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Candidate IDs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoices)) { foreach ($invoices as $inv) { ?>
                        <tr>
                            <td><?php echo $inv->id; ?></td>
                            <td><?php echo htmlspecialchars($inv->period); ?></td>
                            <td><?php echo number_format((float)$inv->invoice_amount, 2); ?></td>
                            <td><?php echo htmlspecialchars($inv->status); ?></td>
                            <td><?php echo htmlspecialchars($inv->created_date); ?></td>
                            <td><pre><?php echo htmlspecialchars($inv->candidate_ids); ?></pre></td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="6">No invoices found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>

</body>
</html>


