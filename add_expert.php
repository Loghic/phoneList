<?php
include 'dbConnection.php';

// Error handling for database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expertName = $_POST['new_expert_name'];
    $expertPhone = $_POST['new_expert_phone'];
    $systemId = $_POST['system_id']; // System ID to associate with

    // Insert new expert into Experts table
    $stmt = $conn->prepare("INSERT INTO Expert_person (name, Private_phone) VALUES (?, ?)");
    $stmt->bind_param("ss", $expertName, $expertPhone);

    if ($stmt->execute()) {
        $expertId = $stmt->insert_id; // Get the ID of the newly inserted expert

        // Debugging: Check if the systemId exists
        $checkSystemStmt = $conn->prepare("SELECT Id FROM Expert WHERE Id = ?");
        $checkSystemStmt->bind_param("i", $systemId);
        $checkSystemStmt->execute();
        $checkSystemStmt->store_result();

        if ($checkSystemStmt->num_rows === 0) {
            echo "Invalid system_id: No matching system found.";
            $stmt->close();
            $conn->close();
            exit;
        }

        // Associate the new expert with the system in Expert_system_person table
        $stmt = $conn->prepare("INSERT INTO Expert_system_person (system_id, person_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $systemId, $expertId);

        if ($stmt->execute()) {
            echo "New expert added and assigned successfully.";
        } else {
            echo "Failed to associate the expert with the system: " . $stmt->error;
        }
    } else {
        echo "Failed to add new expert: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
