<?php
include 'dbConnection.php';

// Error handling for database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$system_id = $_POST['system_id'];
$expert_id = $_POST['expert_id'];
$phone = $_POST['phone'];

// Validate phone number format
if (!preg_match('/^\+?\d*$/', $phone)) {
    die("Invalid phone number format. It should start with an optional + followed by digits.");
}

// Update expert assignment
$stmt = $conn->prepare("
    UPDATE Expert_system_person
    SET Person_id = ?
    WHERE System_id = ?
");
$stmt->bind_param("ii", $expert_id, $system_id);
if ($stmt->execute()) {
    // Update phone number only if a specific expert is selected
    if ($expert_id !== 'new') {
        $stmt = $conn->prepare("
            UPDATE Expert_person
            SET Private_phone = ?
            WHERE Id = ?
        ");
        $stmt->bind_param("si", $phone, $expert_id);
        if ($stmt->execute()) {
            echo "Expert details updated successfully.";
        } else {
            echo "Error updating phone number.";
        }
    } else {
        echo "Expert assignment updated successfully.";
    }
} else {
    echo "Error updating expert assignment.";
}

$stmt->close();
$conn->close();
?>
