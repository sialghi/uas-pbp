<?php
// Test koneksi dan buat tabel reading_progress jika belum ada
// (Project ini pakai DB: epub_fst, port 3306)
$conn = new mysqli('127.0.0.1', 'root', '', 'epub_fst', 3306);

if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✓ Database connected!\n";

// Buat tabel reading_progress
$sql = "CREATE TABLE IF NOT EXISTS `reading_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `reading_seconds` int(11) DEFAULT 0,
  `last_position` text DEFAULT NULL,
  `last_read` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_book` (`user_id`, `book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if($conn->query($sql)) {
    echo "✓ Table reading_progress created/verified!\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'reading_progress'");
if($result->num_rows > 0) {
    echo "✓ Table exists! Checking structure...\n";
    $fields = $conn->query("DESCRIBE reading_progress");
    while($row = $fields->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "✗ Table does not exist!\n";
}

$conn->close();
?>
