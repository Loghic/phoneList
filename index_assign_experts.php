<?php
include 'dbConnection.php';

// Fetch all experts that are NOT associated with the given system
$expertsStmt = $conn->prepare("
    SELECT e.Id AS id, e.name AS name, e.Private_phone AS phone
    FROM Expert_person e
    ORDER BY name ASC
");

$expertsStmt->execute();
$experts = $expertsStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telephone List Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Telephone List Management</h1>
        <h2 class="text-center mb-4">Assign Systems to the Experts</h2>
        <div class="text-center mb-4 top-buttons">
            <button id="assign-choose-systems-button" class="btn btn-success">Choose Assignment of the Systems</button>
        </div>

        <div class="header-row">
            <div class="cell">Expert</div>
            <div class="cell">Phone</div>
            <div class="cell">Number of Systems</div>
            <div class="cell"></div>
        </div>

        <?php while ($expert = $experts->fetch_assoc()): 
        ?>
        <div class="data-row">
            <div class="cell cellExp"><?= htmlspecialchars($expert['name']) ?></div>
            <div class="cell cellExp"><?= htmlspecialchars($expert['phone'] ?? '') ?></div>
            <div class="cell cellExp"><?= htmlspecialchars($expert['id'] ?? '') ?></div>
            <div class="cell cellExp">
                <a href="#" class="btn btn-success edit-button action-button"
                data-system_id="<?= htmlspecialchars($expert['id']) ?>">Assign</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <script>
        $(document).ready(function() {
            $("#assign-choose-systems-button").click(function() {
                window.location.href = 'index.php'; 
            });
        });
    </script>
</body>
