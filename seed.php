<?php
require_once 'db.php';
header('Content-Type: text/html; charset=utf-8');
init_db();
$pdo = get_connection();

$phone  = '+254712345678';
$name   = 'Kasy Jonan';
$income = 85000;
$today  = date('Y-m-d');

function d($offset) { return date('Y-m-d', strtotime("-{$offset} days")); }

// ── User ────────────────────────────────────────────────────────────────────
$pdo->prepare("INSERT INTO users (phone, name, income, employment_type, streak_count, last_active_date)
               VALUES (?, ?, ?, 'employed', 7, ?)
               ON DUPLICATE KEY UPDATE name=VALUES(name), income=VALUES(income),
               employment_type=VALUES(employment_type), streak_count=VALUES(streak_count),
               last_active_date=VALUES(last_active_date)")
    ->execute([$phone, $name, $income, $today]);

$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$uid = $stmt->fetchColumn();

// ── Clear old test data ──────────────────────────────────────────────────────
$pdo->prepare("DELETE FROM entries WHERE user_id = ?")->execute([$uid]);
$pdo->prepare("DELETE FROM budgets WHERE user_id = ?")->execute([$uid]);
$pdo->prepare("DELETE FROM goals   WHERE user_id = ?")->execute([$uid]);

// ── Entries ──────────────────────────────────────────────────────────────────
$entries = [
    [1001, $uid, 'income',  85000,  null,          'Salary',     'Monthly salary',              d(2)],
    [1002, $uid, 'expense', 18000,  'Rent',         null,         'Bedsitter - Kileleshwa',      d(2)],
    [1003, $uid, 'expense',  2800,  'Food',         null,         'Naivas groceries',            d(1)],
    [1004, $uid, 'expense',   600,  'Transport',    null,         'Bolt - town to office',       d(1)],
    [1005, $uid, 'expense',  1200,  'Food',         null,         'Lunch + coffee',              d(0)],
    [1006, $uid, 'expense',  4500,  'BlackTax',     null,         'Sent home - parents',         d(3)],
    [1007, $uid, 'income',  12000,  null,          'SideHustle', 'Freelance design project',    d(5)],
    [1008, $uid, 'expense',  3200,  'Utilities',    null,         'KPLC + Safaricom home fiber', d(4)],
    [1009, $uid, 'expense',  1800,  'Subscriptions',null,         'Netflix, Spotify, iCloud',    d(6)],
    [1010, $uid, 'expense',  5000,  'Shopping',     null,         'Clothes - The Hub Karen',     d(7)],
    [1011, $uid, 'expense',  2200,  'Food',         null,         'Team lunch',                  d(8)],
    [1012, $uid, 'expense',  1500,  'Transport',    null,         'Uber - weekend',              d(9)],
    [1013, $uid, 'income',   5000,  null,          'Crypto',     'USDT profit',                 d(10)],
    [1014, $uid, 'expense',  9500,  'Rent',         null,         'Utilities split',             d(15)],
];
$ins = $pdo->prepare("INSERT IGNORE INTO entries (id, user_id, type, amount, category, source, note, date, currency) VALUES (?,?,?,?,?,?,?,?,'KES')");
foreach ($entries as $e) $ins->execute($e);

// ── Budgets ──────────────────────────────────────────────────────────────────
$budgets = [
    [2001, $uid, 'expense', 'Food',          12000],
    [2002, $uid, 'expense', 'Transport',      6000],
    [2003, $uid, 'expense', 'Entertainment',  4000],
    [2004, $uid, 'expense', 'Shopping',       8000],
];
$ins = $pdo->prepare("INSERT IGNORE INTO budgets (id, user_id, type, category, limit_amount, currency) VALUES (?,?,?,?,?,'KES')");
foreach ($budgets as $b) $ins->execute($b);

// ── Goals ────────────────────────────────────────────────────────────────────
$goals = [
    [3001, $uid, 'Emergency Fund', 150000, 42000, 'emergency'],
    [3002, $uid, 'MacBook Pro',    220000, 68000, 'gadget'],
];
$ins = $pdo->prepare("INSERT IGNORE INTO goals (id, user_id, name, target, saved, type, currency) VALUES (?,?,?,?,?,?,'KES')");
foreach ($goals as $g) $ins->execute($g);

?>
<!DOCTYPE html>
<html>
<head>
  <title>FinTrack Seeder</title>
  <style>
    body { font-family: sans-serif; max-width: 520px; margin: 60px auto; padding: 20px; background: #f8f9fa; }
    .card { background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
    h2 { margin: 0 0 8px; color: #16a34a; }
    .cred { background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 10px; padding: 16px; margin: 16px 0; font-family: monospace; }
    .cred b { color: #15803d; }
    .note { color: #6b7280; font-size: 13px; margin-top: 12px; }
    a { color: #16a34a; }
  </style>
</head>
<body>
  <div class="card">
    <h2>✅ Test data seeded</h2>
    <p>User <strong><?= htmlspecialchars($name) ?></strong> (ID <?= $uid ?>) has been created with 14 sample entries, 4 budgets, and 2 savings goals.</p>
    <div class="cred">
      <b>Phone:</b> 0712345678<br>
      <b>OTP:</b> 1 2 3 4 5 &nbsp;(any 5 digits work)<br>
      <b>Income:</b> KES 85,000 / month
    </div>
    <p class="note">⚠️ Re-running this page clears and re-seeds the test user's data. Delete this file before going to production.</p>
    <a href="index.html">← Open FinTrack</a>
  </div>
</body>
</html>
