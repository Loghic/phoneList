<?php
include 'dbConnection.php';

// Set the response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [];

// Fetch all experts, regardless of the system_id
$allExpertsStmt = $conn->prepare("
    SELECT e.Id, e.name, e.Private_phone
    FROM Expert_person e
    ORDER BY e.name ASC
");
$allExpertsStmt->execute();
$allExpertsResult = $allExpertsStmt->get_result();
$response['experts'] = [];
while ($row = $allExpertsResult->fetch_assoc()) {
    $response['experts'][] = $row;
}

// Fetch distinct systems
$allSystemsStmt = $conn->prepare("
    SELECT DISTINCT esp.System_id AS System_id, e.Desc AS System_name
    FROM Expert_system_person AS esp
    JOIN Expert e ON esp.System_id = e.Id
    ORDER BY System_name ASC
");
$allSystemsStmt->execute();
$systemsResult = $allSystemsStmt->get_result();
$response['systems'] = [];
while ($row = $systemsResult->fetch_assoc()) {
    $response['systems'][] = $row;
}

// Fetch the systems associated with each expert
$systemsStmt = $conn->prepare("
    SELECT 
        e.Id AS expert_id,
        esp.System_id
    FROM Expert_person e
    JOIN Expert_system_person esp ON e.Id = esp.Person_id
");
$systemsStmt->execute();
$systemsResult = $systemsStmt->get_result();
$expertSystems = [];
while ($row = $systemsResult->fetch_assoc()) {
    $expert_id = $row['expert_id'];
    $system_id = $row['System_id'];
    if (!isset($expertSystems[$expert_id])) {
        $expertSystems[$expert_id] = [];
    }
    $expertSystems[$expert_id][] = $system_id;
}
$response['expert_systems'] = $expertSystems;

// Debugging output
error_log('All Experts List: ' . print_r($response['experts'], true));

// Output the response as JSON
echo json_encode($response);

// Close statements and connection
$allExpertsStmt->close();
$allSystemsStmt->close();
$systemsStmt->close();
$conn->close();
?>
