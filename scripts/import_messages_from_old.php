<?php

/**
 * Import messages from the old Loopia database into the new local database.
 *
 * Usage (from recway-new project root):
 *   php scripts/import_messages_from_old.php dev      ← imports from dev_customer DB
 *   php scripts/import_messages_from_old.php main     ← imports from new_customer DB
 *
 * What it does:
 *   1. Reads status_services (still has msg_col) from old DB → builds column→status_id map.
 *   2. Reads every messages row from old DB.
 *   3. Converts each row into a JSON templates object.
 *   4. Upserts into the new local DB's messages table.
 *
 * Safe to run multiple times — uses INSERT … ON DUPLICATE KEY UPDATE.
 */

// ─── Config ──────────────────────────────────────────────────────────────────

$OLD_DBS = [
    // Remote originals (may be blocked by IP whitelist — export locally first)
    'dev' => [
        'host' => 'mysql117.loopia.se',
        'port' => 3306,
        'dbname' => 'orderspi_se_db_1',
        'user' => 'hamza@o373379',
        'password' => 'devdbo373379',
    ],
    'main' => [
        'host' => 'mysql512.loopia.se',
        'port' => 3306,
        'dbname' => 'orderspi_se',
        'user' => 'orderspi@o323952',
        'password' => 'main_server334_?o323952*99',
    ],
    // Local imports (after exporting via phpMyAdmin and importing to WAMP)
    'dev_local' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'recway_old_dev',   // ← change if you named it differently
        'user' => 'root',
        'password' => '',
    ],
    'main_local' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'recway',  // ← change if you named it differently
        'user' => 'root',
        'password' => '',
    ],
];

$NEW_DB = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'new_recway',
    'user' => 'root',
    'password' => '',
];

/** Columns that map to named special keys (not status-based). */
const SPECIAL_KEYS = ['cus_msg', 'admin_msg', 'staff_msg'];

// ─── CLI arg ─────────────────────────────────────────────────────────────────

$target = $argv[1] ?? null;
if (! $target || ! isset($OLD_DBS[$target])) {
    echo "Usage: php scripts/import_messages_from_old.php [dev|main]\n";
    exit(1);
}

$oldCfg = $OLD_DBS[$target];

// ─── Connect ─────────────────────────────────────────────────────────────────

echo "Connecting to OLD DB ({$oldCfg['dbname']} @ {$oldCfg['host']})…\n";
$oldPdo = connect($oldCfg);

echo "Connecting to NEW DB ({$NEW_DB['dbname']} @ {$NEW_DB['host']})…\n";
$newPdo = connect($NEW_DB);

// ─── Step 1: build column→status_id map from old status_services ─────────────

echo "Reading status_services mapping from old DB…\n";

// Shape: [ service_id => [ 'pending_msg' => 15, 'booked_msg' => 8, ... ] ]
$colToStatusByService = [];

$ssRows = $oldPdo->query(
    "SELECT service_id, status_id, msg_col
     FROM   status_services
     WHERE  msg_col IS NOT NULL AND msg_col != ''
     ORDER  BY status_id"   // deterministic: last status wins on duplicates
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($ssRows as $row) {
    $colToStatusByService[$row['service_id']][$row['msg_col']] = $row['status_id'];
}

$serviceCount = count($colToStatusByService);
echo "  Found mappings for {$serviceCount} service type(s).\n";

// ─── Step 2: discover old message columns (all non-system columns) ────────────

$allOldCols = $oldPdo->query(
    "SELECT COLUMN_NAME
     FROM   INFORMATION_SCHEMA.COLUMNS
     WHERE  TABLE_SCHEMA = DATABASE()
       AND  TABLE_NAME   = 'messages'
       AND  COLUMN_NAME NOT IN ('id','cus_id','interview_id','created_at','updated_at')"
)->fetchAll(PDO::FETCH_COLUMN);

if (empty($allOldCols)) {
    echo "ERROR: No template columns found in old messages table. Aborting.\n";
    exit(1);
}

$escapedCols = implode(', ', array_map(fn ($c) => "`{$c}`", $allOldCols));
echo "  Old columns found: " . count($allOldCols) . "\n";

// ─── Step 3: stream old messages rows and upsert into new DB ─────────────────

echo "Importing messages…\n";

$stmt = $oldPdo->query(
    "SELECT id, cus_id, interview_id, {$escapedCols} FROM messages ORDER BY id"
);

$upsertSql = "
    INSERT INTO messages (cus_id, interview_id, templates)
    VALUES (:cus_id, :interview_id, :templates)
    ON DUPLICATE KEY UPDATE templates = VALUES(templates)
";
$upsert = $newPdo->prepare($upsertSql);

$inserted = 0;
$skipped = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $serviceId = (int) $row['interview_id'];
    $colMapping = $colToStatusByService[$serviceId] ?? [];
    $templates = [];

    foreach ($allOldCols as $col) {
        $value = $row[$col] ?? null;
        if ($value === null || $value === '') {
            continue;
        }

        if (in_array($col, SPECIAL_KEYS, true)) {
            // Always preserve special keys under their original name.
            $templates[$col] = $value;
        } elseif (isset($colMapping[$col])) {
            // Map to status_id string key.
            $templates[(string) $colMapping[$col]] = $value;
        } else {
            // No mapping — keep original column name so no data is lost.
            $templates[$col] = $value;
        }
    }

    if (empty($templates)) {
        $skipped++;
        continue;
    }

    $upsert->execute([
        ':cus_id' => $row['cus_id'],
        ':interview_id' => $row['interview_id'],
        ':templates' => json_encode($templates, JSON_UNESCAPED_UNICODE),
    ]);

    $inserted++;
}

echo "Done.\n";
echo "  Rows imported : {$inserted}\n";
echo "  Rows skipped  : {$skipped} (all columns were empty)\n";

// ─── Summary ─────────────────────────────────────────────────────────────────

$total = $newPdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
echo "  Total rows in new messages table: {$total}\n";

// ─── Helper ──────────────────────────────────────────────────────────────────

function connect(array $cfg): PDO
{
    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "  Connected.\n";
        return $pdo;
    } catch (PDOException $e) {
        echo "  FAILED: " . $e->getMessage() . "\n";
        exit(1);
    }
}
