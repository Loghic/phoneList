<?php
include 'dbConnection.php';

$system_id = $_GET['system_id'];

// Fetch the current expert details
$stmt = $conn->prepare("
    SELECT exp.Desc as system_name, e.name as expert_name, e.Private_phone as phone, e.Id as expert_id
    FROM Expert_person e
    JOIN Expert_system_person esp ON e.Id = esp.Person_id
    JOIN Expert exp ON esp.System_id = exp.id
    WHERE esp.System_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $system_id);
$stmt->execute();
$result = $stmt->get_result();
$expert = $result->fetch_assoc();

// Fetch all experts for the dropdown in ascending order
$expertsStmt = $conn->prepare("
    SELECT Id, name
    FROM Expert_person
    ORDER BY name ASC
");
$expertsStmt->execute();
$expertsResult = $expertsStmt->get_result();
$experts = [];
while ($row = $expertsResult->fetch_assoc()) {
    $experts[] = $row;
}

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
