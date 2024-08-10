<?php
include 'dbConnection.php';

// Error handling for database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$new_expert_name = $_POST['new_expert_name'];
$new_expert_phone = $_POST['new_expert_phone'];

// Validate phone number
if (empty($new_expert_phone)) {
    die("Phone number cannot be empty.");
}

// Validate phone number format
if (!preg_match('/^\+?\d*$/', $new_expert_phone)) {
    die("Invalid phone number format. It should start with an optional + followed by digits.");
}

// Insert new expert into the database
$stmt = $conn->prepare("
    INSERT INTO Expert_person (name, Private_phone)
    VALUES (?, ?)
");
$stmt->bind_param("ss", $new_expert_name, $new_expert_phone);
if ($stmt->execute()) {
    echo "New expert added successfully.";
} else {
    echo "Error adding new expert.";
}

$stmt->close();
$conn->close();
?>
