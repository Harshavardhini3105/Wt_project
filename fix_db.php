<?php
include 'db.php';
mysqli_query($conn, "DROP TABLE IF EXISTS tasks");
$sql = "CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    duration_mins INT NOT NULL,
    deadline DATETIME NOT NULL,
    status ENUM('pending', 'completed', 'missed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if(mysqli_query($conn, $sql)) {
    echo "Tasks table recreated successfully!";
} else {
    echo "Error: ".mysqli_error($conn);
}
?>
