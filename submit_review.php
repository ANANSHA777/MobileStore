<?php
/**
 * submit_review.php — Handles Product & Delivery Feedback Submissions
 */
session_start();
require_once 'db.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user     =$_SESSION['user'];
$order_id = (int)($_POST['order_id'] ?? 0);
$rating   = max(1, min(5, (int)($_POST['rating'] ?? 5)));
$comment  = trim($_POST['comment'] ?? '');

if ($order_id <= 0 \vert{}\vert{} ($user['role'] ?? '') === 'admin') {
    header('Location: user_dashboard.php');
    exit;
}

// Verify order ownership and delivered status
$stmt =$pdo->prepare('SELECT id, status FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$order_id,$user['id']]);
$order =$stmt->fetch();

if (!$order \vert{}\vert{} strtolower($order['status']) !== 'delivered') {
    header("Location: order_detail.php?id={$order_id}");
    exit;
}

try {
    // Ensure order_reviews table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_order (order_id, user_id)
    )");

    // Insert or Update feedback
    $check =$pdo->prepare('SELECT id FROM order_reviews WHERE order_id = ? AND user_id = ? LIMIT 1');
    $check->execute([$order_id,$user['id']]);

    if ($check->fetch()) {
        $update =$pdo->prepare('UPDATE order_reviews SET rating = ?, comment = ? WHERE order_id = ? AND user_id = ?');
        $update->execute([$rating,$comment, $order_id,$user['id']]);
    } else {
        $insert = $pdo->prepare('INSERT INTO order_reviews (order_id, user_id, rating, comment) VALUES (?, ?, ?, ?)');$insert->execute([$order_id,$user['id'], $rating,$comment]);
    }

    $_SESSION['feedback_success'] = 'Thank you! Your feedback has been submitted.';
} catch (PDOException $e) {$_SESSION['feedback_error'] = 'Could not save feedback. Please try again.';
}

header("Location: order_detail.php?id={$order_id}");
exit;