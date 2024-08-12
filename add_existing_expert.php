<?php
include 'dbConnection.php';

// Error handling for database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expertId = $_POST['expert_id'];
    $phone = $_POST['phone'];
    $systemId = $_POST['system_id'];

    // Validate phone number
    if (empty($phone)) {
        die("Phone number cannot be empty.");
    }
    if (!preg_match('/^\+?\d*$/', $phone)) {
        die("Invalid phone number format. It should start with an optional + followed by digits.");
    }

    // Update the expert's phone number
    $updateExpertStmt = $conn->prepare("UPDATE Expert_person SET Private_phone = ? WHERE Id = ?");
    $updateExpertStmt->bind_param("si", $phone, $expertId);

    if ($updateExpertStmt->execute()) {
        // Check if the systemId exists
        $checkSystemStmt = $conn->prepare("SELECT Id FROM Expert WHERE Id = ?");
        $checkSystemStmt->bind_param("i", $systemId);
        $checkSystemStmt->execute();
        $checkSystemStmt->store_result();

        if ($checkSystemStmt->num_rows === 0) {
            die("Invalid system_id: No matching system found.");
        }

        // Associate the expert with the system
        $associateStmt = $conn->prepare("INSERT INTO Expert_system_person (system_id, person_id) VALUES (?, ?)
                                        ON DUPLICATE KEY UPDATE person_id = VALUES(person_id)");
        $associateStmt->bind_param("ii", $systemId, $expertId);

        if ($associateStmt->execute()) {
            echo "Expert updated and assigned to the system successfully.";
        } else {
            die("Failed to associate the expert with the system: " . $associateStmt->error);
        }

        $associateStmt->close();
        $checkSystemStmt->close();
    } else {
        die("Failed to update expert phone number: " . $updateExpertStmt->error);
    }

    $updateExpertStmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
