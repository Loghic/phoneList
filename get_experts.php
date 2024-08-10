<?php
include 'dbConnection.php';

$system_id = $_GET['system_id'];

// Fetch the current expert details
$stmt = $conn->prepare("
    SELECT 
    exp.Desc AS system_name,
    COALESCE(e_assigned.name, e.name) AS expert_name,
    COALESCE(e_assigned.Private_phone, e.Private_phone) AS phone,
    COALESCE(exp.Assigned_expert_id, e.Id) AS expert_id
    FROM Expert exp
    LEFT JOIN Expert_person e_assigned ON exp.Assigned_expert_id = e_assigned.Id
    JOIN Expert_system_person esp ON exp.id = esp.System_id
    JOIN Expert_person e ON e.Id = esp.Person_id
    WHERE esp.System_id = ?
    LIMIT 1;
");
$stmt->bind_param("i", $system_id);
$stmt->execute();
$result = $stmt->get_result();
$expert = $result->fetch_assoc();

// Debugging output
error_log('Expert Data: ' . print_r($expert, true));

// Fetch only experts associated with the given system
$expertsStmt = $conn->prepare("
    SELECT e.Id, e.name
    FROM Expert_person e
    JOIN Expert_system_person esp ON e.Id = esp.Person_id
    WHERE esp.System_id = ?
    ORDER BY e.name ASC
");
$expertsStmt->bind_param("i", $system_id);
$expertsStmt->execute();
$expertsResult = $expertsStmt->get_result();
$experts = [];
while ($row = $expertsResult->fetch_assoc()) {
    $experts[] = $row;
}

// Debugging output
error_log('Experts List: ' . print_r($experts, true));

// Create the response array
$response = [
    'expert' => $expert,
    'experts' => $experts
];

// Set the header and output the response
header('Content-Type: application/json');
echo json_encode($response);

// Close statements and connection
$stmt->close();
$expertsStmt->close();
$conn->close();
?>
