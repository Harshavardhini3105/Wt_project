<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];
$task_id = intval($_POST['task_id'] ?? 0);

if ($task_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid task ID']);
    exit();
}

// Update the task status
$stmt = $conn->prepare("UPDATE tasks SET status='completed' WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $task_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Task not found or already completed']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close();
?>
