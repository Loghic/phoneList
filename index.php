<?php
include 'dbConnection.php';
include 'functions.php';

// Error handling for database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Disable ONLY_FULL_GROUP_BY mode securely
$conn->query("SET SESSION sql_mode=(SELECT REPLACE(@@SESSION.sql_mode, 'ONLY_FULL_GROUP_BY', ''))");

// Fetch distinct systems
$systemsStmt = $conn->prepare("SELECT DISTINCT System_id FROM Expert_system_person");
$systemsStmt->execute();
$systems = $systemsStmt->get_result();
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
        <h2 class="text-center mb-4">Assign Experts to Systems</h2>
        <h2 class="text-center mb-4">Current Schedules</h2>
        <div class="text-center mb-4">
            <button id="assign-experts-button" class="btn btn-primary">Assign Experts Randomly</button>
        </div>

        <div class="header-row">
            <div class="cell">System</div>
            <div class="cell">Expert</div>
            <div class="cell">Phone</div>
            <div class="cell">Actions</div>
        </div>

        <?php while ($system = $systems->fetch_assoc()): 
            $expert = getAssignedExpert($system['System_id'], $conn);
        ?>
        <div class="data-row">
            <div class="cell"><?= htmlspecialchars($expert['system_name']) ?></div>
            <div class="cell"><?= htmlspecialchars($expert['expert_name'] ?? 'N/A') ?></div>
            <div class="cell"><?= htmlspecialchars($expert['phone'] ?? 'N/A') ?></div>
            <div class="cell">
                <a href="#" class="btn btn-success edit-button action-button" data-system_id="<?= htmlspecialchars($system['System_id']) ?>">Edit</a>
            </div>
        </div>
        <?php endwhile; ?>

        <!-- Edit Dialog -->
        <div id="edit-dialog" title="Edit Expert Assignment" style="display:none;">
            <form id="edit-form">
                <div class="form-group">
                    <label for="system_name">System:</label>
                    <span id="sys_name"></span>
                </div>
                <div class="form-group">
                    <label for="expert_name">Current Expert:</label>
                    <span id="current_expert"></span>
                </div>
                <div class="form-group">
                    <label for="expert_select">Select New Expert:</label>
                    <select id="expert_select" name="expert_id" class="form-control">
                    </select>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" class="form-control" pattern="^\+?\d*$" placeholder="Enter phone number">
                </div>
                <input type="hidden" id="system_id" name="system_id">
                <button type="button" id="add-expert-btn" class="btn btn-secondary">Add New Expert</button>
                <button type="button" id="save-only-btn" class="btn btn-primary">Save</button>
            </form>
        </div>

        <!-- Add New Expert Dialog -->
        <div id="add-expert-dialog" title="Add New Expert" style="display:none;">
            <form id="add-expert-form">
                <input type="hidden" id="system_id" name="system_id">
                <div class="form-group">
                    <label for="new_expert_name">Expert Name:</label>
                    <input type="text" id="new_expert_name" name="new_expert_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_expert_phone">Phone:</label>
                    <input type="tel" id="new_expert_phone" name="new_expert_phone" class="form-control" pattern="^\+?\d*$" required>
                </div>
                <button type="button" id="save-and-add" class="btn btn-primary">Add Expert</button>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $("#assign-experts-button").click(function() {
            $.ajax({
                url: 'assign_experts.php',
                type: 'GET',
                success: function(response) {
                    alert(response);
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert("An error occurred: " + error);
                }
            });
        });

        function refreshExpertDropdown(selectedExpertId) {
            var systemId = $("#system_id").val();

            $.ajax({
                url: 'get_experts.php',
                type: 'GET',
                data: { system_id: systemId },
                dataType: 'json',
                success: function(data) {
                    var $expertSelect = $("#expert_select");
                    $expertSelect.empty();

                    $.each(data.experts, function(index, expert) {
                        $expertSelect.append(
                            $('<option>', { value: expert.Id, text: expert.name })
                        );
                    });

                    if (selectedExpertId) {
                        $expertSelect.val(selectedExpertId);
                    }
                }
            });
        }

        $("#edit-dialog").dialog({
            autoOpen: false,
            modal: true,
            buttons: {
                "Save and Exit": function() {
                    $.ajax({
                        url: 'update_expert.php',
                        type: 'POST',
                        data: $("#edit-form").serialize(),
                        success: function(response) {
                            alert(response);
                            $("#edit-dialog").dialog("close");
                            refreshExpertDropdown();
                            location.reload();
                        }
                    });
                },
                "Cancel": function() {
                    $(this).dialog("close");
                    location.reload();
                },
            },
            close: function() {
                location.reload(); // Reload the page when dialog close button (X) is clicked
            },
            classes: {
                "ui-dialog": "my-dialog",
                "ui-dialog-titlebar": "my-dialog-titlebar"
            }
        });

        $("#add-expert-dialog").dialog({
            autoOpen: false,
            modal: true,
            buttons: {
                "Add and Exit": function() {
                    $("#add-expert-form").submit();
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
            },
            classes: {
                "ui-dialog": "my-dialog",
                "ui-dialog-titlebar": "my-dialog-titlebar"
            }
        });

        $(".edit-button").click(function(e) {
            e.preventDefault();
            var systemId = $(this).data("system_id");

            $.ajax({
                url: 'get_experts.php',
                type: 'GET',
                data: { system_id: systemId },
                dataType: 'json',
                success: function(data) {
                    $("#system_name").text(data.expert.system_name);
                    $("#phone").val(data.expert.phone);
                    $("#system_id").val(systemId);

                    var selectedExpertId = data.expert.expert_id;

                    refreshExpertDropdown(selectedExpertId);

                    $("#current_expert").text(data.expert.expert_name);
                    $("#sys_name").text(data.expert.system_name);

                    $("#edit-dialog").dialog("open");
                }
            });
        });

        $("#save-only-btn").click(function() {
            $.ajax({
                url: 'update_expert.php',
                type: 'POST',
                data: $("#edit-form").serialize(),
                success: function(response) {
                    if (response.trim() === "Expert assignment and phone number updated successfully.") {
                        alert("Expert details updated successfully.");
                        var newExpertName = $("#expert_select option:selected").text();
                        $("#current_expert").text(newExpertName);
                        $.ajax({
                            url: 'get_expert_details.php',
                            type: 'GET',
                            data: { system_id: $("#system_id").val() },
                            dataType: 'json',
                            success: function(data) {
                                $("#system_name").text(data.expert.system_name);
                                $("#phone").val(data.expert.phone);
                                $("#expert_select").val(data.expert.expert_id);
                                $("#expert_name").val(data.expert.expert_name);
                            }
                        });
                    } else {
                        alert("Error: " + response);
                    }
                },
                error: function(xhr, status, error) {
                    alert("An unexpected error occurred: " + error);
                }
            });
        });

        $("#add-expert-btn").click(function() {
            var systemId = $("#system_id").val();
            $("#add-expert-form #system_id").val(systemId);
            $("#add-expert-dialog").dialog("open");
        });

        $("#add-expert-form").submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'add_expert.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    alert(response);
                    $("#add-expert-dialog").dialog("close");
                    refreshExpertDropdown();
                },
                error: function(xhr, status, error) {
                    alert("An error occurred: " + error);
                }
            });
        });

        $("#save-and-add").click(function() {
            $.ajax({
                url: 'add_expert.php',
                type: 'POST',
                data: $("#add-expert-form").serialize(),
                success: function(response) {
                    alert(response);
                    refreshExpertDropdown();
                }
            });
        });

        $("#expert_select").change(function() {
            var selectedExpertId = $(this).val();
            if (selectedExpertId === 'new') {
                $("#expert_name").prop('readonly', false);
                $("#phone").val('');
            } else {
                $("#expert_name").prop('readonly', true);
                $.ajax({
                    url: 'get_expert_details.php',
                    type: 'GET',
                    data: { expert_id: selectedExpertId },
                    dataType: 'json',
                    success: function(data) {
                        $("#expert_name").val(data.name);
                        $("#phone").val(data.phone);
                    }
                });
            }
        });
    });
    </script>
</body>
</html>

