<?php
require_once 'db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
init_db();
$pdo = get_connection();
function send_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
if ($action === 'auth') {
    $phone = $input['phone'] ?? '';
    if (!$phone) send_response(['error' => 'Phone required'], 400);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        // Streak Logic
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $lastActive = $user['last_active_date'];
        
        if ($lastActive === $yesterday) {
            $user['streak_count']++;
        } elseif ($lastActive !== $today) {
            $user['streak_count'] = 1;
        }
        
        $user['last_active_date'] = $today;
        $pdo->prepare("UPDATE users SET streak_count = ?, last_active_date = ? WHERE id = ?")
            ->execute([$user['streak_count'], $today, $user['id']]);

        $entries = $pdo->prepare("SELECT * FROM entries WHERE user_id = ? ORDER BY id DESC");
        $entries->execute([$user['id']]);
        $budgets = $pdo->prepare("SELECT * FROM budgets WHERE user_id = ?");
        $budgets->execute([$user['id']]);
        $goals = $pdo->prepare("SELECT * FROM goals WHERE user_id = ?");
        $goals->execute([$user['id']]);
        send_response(['user' => $user, 'entries' => $entries->fetchAll(PDO::FETCH_ASSOC), 'budgets' => $budgets->fetchAll(PDO::FETCH_ASSOC), 'goals' => $goals->fetchAll(PDO::FETCH_ASSOC)]);
    } else send_response(['new_user' => true]);
}
if ($action === 'setup') {
    $phone = $input['phone'] ?? ''; $name = $input['name'] ?? ''; $income = $input['income'] ?? 0; $type = $input['type'] ?? 'individual';
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO users (phone, name, income, employment_type, last_active_date, streak_count) VALUES (?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE name = VALUES(name), income = VALUES(income), employment_type = VALUES(employment_type), last_active_date = VALUES(last_active_date)");
    $stmt->execute([$phone, $name, $income, $type, $today]);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    send_response(['user' => $stmt->fetch(PDO::FETCH_ASSOC)]);
}
if ($action === 'save_entry') {
    $userId = $input['user_id'] ?? 0; $entry = $input['entry'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO entries (id, user_id, type, amount, category, source, note, date, currency) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE amount=VALUES(amount), category=VALUES(category), source=VALUES(source), note=VALUES(note), date=VALUES(date)");
    $stmt->execute([$entry['id'], $userId, $entry['type'], $entry['amount'], $entry['category'] ?? null, $entry['source'] ?? null, $entry['note'] ?? '', $entry['date'], $entry['currency'] ?? 'KES']);
    send_response(['success' => true]);
}
if ($action === 'delete_entry') {
    $pdo->prepare("DELETE FROM entries WHERE id = ? AND user_id = ?")->execute([$input['id'], $input['user_id']]);
    send_response(['success' => true]);
}
if ($action === 'save_budget') {
    $budget = $input['budget'];
    $pdo->prepare("INSERT INTO budgets (id, user_id, type, category, limit_amount, currency) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE limit_amount=VALUES(limit_amount)")->execute([$budget['id'], $input['user_id'], $budget['type'], $budget['category'], $budget['limit'], $budget['currency'] ?? 'KES']);
    send_response(['success' => true]);
}
if ($action === 'delete_budget') {
    $pdo->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?")->execute([$input['id'], $input['user_id']]);
    send_response(['success' => true]);
}
if ($action === 'save_goal') {
    $goal = $input['goal'];
    $pdo->prepare("INSERT INTO goals (id, user_id, name, target, saved, type, currency) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE target=VALUES(target), saved=VALUES(saved), name=VALUES(name)")->execute([$goal['id'], $input['user_id'], $goal['name'], $goal['target'], $goal['saved'], $goal['type'], $goal['currency'] ?? 'KES']);
    send_response(['success' => true]);
}
if ($action === 'delete_goal') {
    $pdo->prepare("DELETE FROM goals WHERE id = ? AND user_id = ?")->execute([$input['id'], $input['user_id']]);
    send_response(['success' => true]);
}
if ($action === 'update_theme') {
    $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?")->execute([$input['theme'], $input['user_id']]);
    send_response(['success' => true]);
}
if ($action === 'clear_all') {
    $pdo->prepare("DELETE FROM entries WHERE user_id = ?")->execute([$input['user_id']]);
    $pdo->prepare("DELETE FROM budgets WHERE user_id = ?")->execute([$input['user_id']]);
    $pdo->prepare("DELETE FROM goals WHERE user_id = ?")->execute([$input['user_id']]);
    send_response(['success' => true]);
}
send_response(['error' => 'Invalid action'], 404);
?>
