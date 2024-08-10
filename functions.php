<?php
// Function to get a randomly assigned expert for each system
function getAssignedExpert($system_id, $conn) {
    // Fetch the system description and all experts associated with the given system_id
    $stmt = $conn->prepare("
        SELECT exp.Desc AS system_name, e.Id AS expert_id, e.name AS expert_name, e.Private_phone AS phone
        FROM Expert exp
        JOIN Expert_system_person esp ON exp.id = esp.System_id
        JOIN Expert_person e ON e.Id = esp.Person_id
        WHERE esp.System_id = ?
    ");
    $stmt->bind_param("i", $system_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch all associated experts into an array
    $experts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // If there are no experts associated with this system, return a default value
    if (empty($experts)) {
        return [
            'system_name' => 'Unknown System',
            'expert_name' => 'N/A',
            'phone' => 'N/A'
        ];
    }

    // Randomly select an expert
    $random_expert = $experts[array_rand($experts)];
    
    // Return expert details
    return [
        'system_name' => $random_expert['system_name'],
        'expert_name' => $random_expert['expert_name'],
        'phone' => $random_expert['phone']
    ];
}
?>