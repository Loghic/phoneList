<?php
// Function to get a randomly assigned expert for each system
function getAssignedExpert($system_id, $conn) {
    $stmt = $conn->prepare("
        SELECT exp.Desc AS system_name, exp.Phone AS system_phone,
        IF(exp.Assigned_expert_id IS NOT NULL, e_assigned.name, e.name) AS expert_name,
        IF(exp.Assigned_expert_id IS NOT NULL, e_assigned.Private_phone, e.Private_phone) AS phone,
        IF(exp.Assigned_expert_id IS NOT NULL, e_assigned.Id, e.Id) AS expert_id
        FROM Expert_system_person esp
        JOIN Expert exp ON esp.System_id = exp.Id
        LEFT JOIN Expert_person e_assigned ON exp.Assigned_expert_id = e_assigned.Id
        JOIN Expert_person e ON e.Id = esp.Person_id
        WHERE esp.System_id = ?
        LIMIT 1;
    ");
    $stmt->bind_param("i", $system_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function assignExpertsRandomly($conn) {
    // Fetch all distinct systems
    $systemsStmt = $conn->prepare("SELECT DISTINCT System_id FROM Expert_system_person");
    $systemsStmt->execute();
    $systems = $systemsStmt->get_result();

    while ($system = $systems->fetch_assoc()) {
        $system_id = $system['System_id'];

        // Fetch all experts associated with the system
        $stmt = $conn->prepare("
            SELECT e.Id AS expert_id
            FROM Expert_system_person esp
            JOIN Expert_person e ON e.Id = esp.Person_id
            WHERE esp.System_id = ?
        ");
        $stmt->bind_param("i", $system_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $experts = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // If there are no experts associated with the system, continue to the next system
        if (empty($experts)) {
            continue;
        }

        // Randomly select an expert
        $new_expert = $experts[array_rand($experts)];
        $expert_id = $new_expert['expert_id'];

        // Also update the Assigned_expert_id in the Expert table
        $updateExpStmt = $conn->prepare("
            UPDATE Expert
            SET Assigned_expert_id = ?
            WHERE Id = ?
        ");
        $updateExpStmt->bind_param("ii", $expert_id, $system_id);
        $updateExpStmt->execute();
        $updateExpStmt->close();
    }

    echo "Experts have been randomly assigned.";
}
?>