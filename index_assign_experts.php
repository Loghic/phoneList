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
                <a href="#" class="btn btn-success assign_systems-button action-button"
                data-expert_id="<?= htmlspecialchars($expert['id']) ?>"
                data-expert_name="<?= htmlspecialchars($expert['name']) ?>"
                data-expert_phone="<?= htmlspecialchars($expert['phone'] ?? '') ?>">Assign</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    
    <!-- Assign Systems to Expert Dialog -->
    <div id="assign_systems-dialog" title="Edit Expert Systems Assignment" style="display:none;">
            <form id="edit-form">
                <div class="form-group">
                    <label for="Expert Name">Expert:</label>
                    <span id="exp_name"></span>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" class="form-control" pattern="^\+?\d*$" placeholder="Enter phone number">
                </div>
                <input type="hidden" id="expert_id" name="expert_id">
                <div class="button-container">
                    <button type="button" id="add-existing-expert-btn" class="btn btn-primary">Assign All Systems</button>
                    <button type="button" id="add-expert-btn" class="btn btn-secondary">Unassign All Systems</button>
                    <button type="button" id="save-only-btn" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>

    <script>
        $(document).ready(function() {
            $("#assign-choose-systems-button").click(function() {
                window.location.href = 'index.php'; 
            });

            $("#assign_systems-dialog").dialog({
                width: 320,
                autoOpen: false,
                modal: true,
                buttons: { 
                    "Save and Exit":{ 
                        text: "Save and Exit",
                        class: "save-exit-button",
                        click: function() {
                            $(this).dialog("close");
                        }
                    },
                    "Cancel": {
                        text: "Cancel",
                        class: "cancel-button", 
                        click: function() {
                            $(this).dialog("close");
                            location.reload();
                        }
                    }
                },
                close: function() {
                    location.reload(); // Reload the page when dialog close button (X) is clicked
                },
                classes: {
                    "ui-dialog": "my-dialog",
                    "ui-dialog-titlebar": "my-dialog-titlebar"
                }
            });

            $(".assign_systems-button").click(function(e) {
                e.preventDefault();
                var expertName = $(this).data("expert_name");
                var expertId = $(this).data("expert_id");
                var expertPhone = $(this).data("expert_phone");

                // Set the expert's name and ID in the dialog
                $("#exp_name").text(expertName);
                $("#expert_id").val(expertId);
                $("#phone").val(expertPhone);

                $("#assign_systems-dialog").dialog("open");
            });
    });
    </script>
</body>
