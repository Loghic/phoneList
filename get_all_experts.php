<?php
include 'dbConnection.php';

$system_id = $_GET['system_id'];

// Fetch the current expert details or just the system name if no expert is associated
$stmt = $conn->prepare("
    SELECT 
        exp.Desc AS system_name,
        COALESCE(e_assigned.name, e.name) AS expert_name,
        COALESCE(e_assigned.Private_phone, e.Private_phone) AS phone,
        COALESCE(exp.Assigned_expert_id, e.Id) AS expert_id
    FROM Expert exp
    LEFT JOIN Expert_person e_assigned ON exp.Assigned_expert_id = e_assigned.Id
    LEFT JOIN Expert_system_person esp ON exp.Id = esp.System_id
    LEFT JOIN Expert_person e ON esp.Person_id = e.Id
    WHERE exp.Id = ?
    LIMIT 1;
");
$stmt->bind_param("i", $system_id);
$stmt->execute();
$result = $stmt->get_result();
$expert = $result->fetch_assoc();

// Debugging output
error_log('Expert Data: ' . print_r($expert, true));

// Fetch all experts, regardless of the system_id
$allExpertsStmt = $conn->prepare("
    SELECT e.Id, e.name
    FROM Expert_person e
    ORDER BY e.name ASC
");
$allExpertsStmt->execute();
$allExpertsResult = $allExpertsStmt->get_result();
$experts = [];
while ($row = $allExpertsResult->fetch_assoc()) {
    $experts[] = $row;
}

// Debugging output
error_log('All Experts List: ' . print_r($experts, true));

// Handle case when no expert data is found
if (!$expert) {
    $response = [
        'status' => 'error',
        'message' => 'No expert data found for the selected system.',
        'expert' => [
            'system_name' => '', // Provide an empty string if not found
            'expert_name' => '',
            'phone' => '',
            'expert_id' => ''
        ],
        'experts' => $experts
    ];
} else {
    // Create the response array
    $response = [
        'status' => 'success',
        'expert' => $expert,
        'experts' => $experts
    ];
}

// Set the header and output the response as JSON
header('Content-Type: application/json');
echo json_encode($response);

// Close statements and connection
$stmt->close();
$allExpertsStmt->close();
$conn->close();
?>
