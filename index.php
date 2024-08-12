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
$systemsStmt = $conn->prepare("SELECT DISTINCT esp.System_id AS System_id, e.Desc AS System_name
                                FROM Expert_system_person AS esp
                                JOIN Expert e ON esp.System_id = e.Id
                                ORDER BY System_name ASC");
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
        <div class="text-center mb-4 top-buttons">
            <button id="assign-experts-button" class="btn btn-warning">Assign Experts Randomly</button>
            <button id="assign-choose-experts-button" class="btn btn-success">Choose Assignment of Experts</button>
        </div>

        <div class="header-row">
            <div class="cell">System</div>
            <div class="cell">Expert</div>
            <div class="cell">Phone</div>
            <div class="cell"></div>
        </div>

        <?php while ($system = $systems->fetch_assoc()): 
            $expert = getAssignedExpert($system['System_id'], $conn);
        ?>
        <div class="data-row">
            <div class="cell">
                <?= htmlspecialchars($expert['system_name']) ?>
                <?php if (!empty($expert['system_phone'])): ?>
                    (<?= htmlspecialchars($expert['system_phone']) ?>)
                <?php endif; ?>
            </div>
            <div class="cell"><?= htmlspecialchars($expert['expert_name'] ?? '') ?></div>
            <div class="cell"><?= htmlspecialchars($expert['phone'] ?? '') ?></div>
            <div class="cell">
                <a href="#" class="btn btn-success edit-button action-button"
                data-system_id="<?= htmlspecialchars($system['System_id']) ?>">Assign</a>
            </div>
        </div>
        <?php endwhile; ?>

        <!-- Expert Assignment Dialog -->
        <div id="exp-assignment-dialog" title="Edit Expert Assignment" style="display:none;">
            <form id="exp-assignment">
                <div class="form-group">
                    <label for="assignment_expert_select">Select Expert:</label>
                    <select id="assignment_expert_select" name="expert_id" class="form-control"></select>
                </div>
                <div class="form-group">
                    <label for="assignment_phone">Phone:</label>
                    <input type="tel" id="assignment_phone" name="phone" class="form-control" pattern="^\+?\d*$" placeholder="Enter phone number">
                </div>
                <div class="form-group">
                    <label for="systems-container">Select Systems:</label>
                    <div id="systems-container"></div>
                </div>
            </form>
        </div>

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
                <div class="button-container">
                <button type="button" id="add-existing-expert-btn" class="btn btn-primary">Add Existing Expert</button>
                    <button type="button" id="add-expert-btn" class="btn btn-secondary">Add New Expert</button>
                    <button type="button" id="save-only-btn" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>

        <!-- Add New Expert Dialog -->
        <div id="add-expert-dialog" title="Add New Expert" style="display:none;">
            <form id="add-expert-form">
                <input type="hidden" id="system_id" name="system_id">
                <div class="form-group">
                    <label for="system_name">System:</label>
                    <span id="sys_name"></span>
                </div>
                <div class="form-group">
                    <label for="new_expert_name">Expert Name:</label>
                    <input type="text" id="new_expert_name" name="new_expert_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_expert_phone">Phone:</label>
                    <input type="tel" id="new_expert_phone" name="new_expert_phone" class="form-control" pattern="^\+?\d*$" required>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Existing Expert Dialog -->
    <div id="add-existing-expert-dialog" title="Add Existing Expert" style="display:none;">
            <form id="add-existing-expert-form">
                <input type="hidden" id="system_id" name="system_id">
                <div class="form-group">
                    <label for="system_name">System:</label>
                    <span id="sys_name"></span>
                </div>
                <div class="form-group">
                    <label for="existing_expert_select">Select New Expert:</label>
                    <select id="existing_expert_select" name="expert_id" class="form-control">
                    </select>
                </div>
                <div class="form-group">
                    <label for="phone_existing">Phone:</label>
                    <input type="tel" id="phone_existing" name="phone" class="form-control" pattern="^\+?\d*$" placeholder="Enter phone number">
                </div>
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

        function refreshAllExpertDropdown(selectedExpertId) {
            var systemId = $("#system_id").val();

            $.ajax({
                url: 'get_all_experts.php',
                type: 'GET',
                data: { system_id: systemId },
                dataType: 'json',
                success: function(data) {
                    var $expertSelect = $("#existing_expert_select");
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

        // Variable to store the selected expert ID
        var selectedAssingmentExpertId = null;

        $("#assign-choose-experts-button").click(function() {
            $.ajax({
                url: 'get_all_experts_and_systems.php', // Replace with your actual PHP endpoint
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Populate expert dropdown
                    var $expertSelect = $("#assignment_expert_select");
                    $expertSelect.empty(); // Clear existing options
                    $.each(data.experts, function(index, expert) {
                        $expertSelect.append(
                            $('<option>', { value: expert.Id, text: expert.name })
                        );
                    });

                    // Handle change event on expert dropdown
                    $expertSelect.change(function() {
                        selectedAssingmentExpertId = $(this).val();
                        populateSystemCheckboxes(data.systems, selectedAssingmentExpertId, data.expert_systems);
                    });

                    $("#exp-assignment-dialog").dialog("open");
                },
                error: function(xhr, status, error) {
                    alert("An error occurred: " + error);
                }
            });
        });

        function populateSystemCheckboxes(systems, expertId, expertSystems) {
            var $systemsContainer = $("#systems-container");
            $systemsContainer.empty(); // Clear existing checkboxes

            $.each(systems, function(index, system) {
                var isChecked = false;
                // Check if the selected expert is associated with the system
                if (expertSystems[expertId] && expertSystems[expertId].includes(system.System_id)) {
                    isChecked = true;
                }
                $systemsContainer.append(
                    $('<div>').append(
                        $('<input>', { 
                            type: 'checkbox', 
                            name: 'system_ids[]', 
                            value: system.System_id,
                            checked: isChecked 
                        }),
                        $('<label>').text(system.System_name)
                    )
                );
            });
        }

        // Initialize the dialog
        $("#exp-assignment-dialog").dialog({
            width: 320,
            autoOpen: false,
            modal: true,
            buttons: {
                "Save Assignments": function() {
                    $.ajax({
                        url: 'save_expert_assignments.php', // Replace with your actual PHP endpoint
                        type: 'POST',
                        data: $("assignment_expert_select").serialize(),
                        success: function(response) {
                            alert(response);
                            $("#exp-assignment-dialog").dialog("close");
                        },
                        error: function(xhr, status, error) {
                            alert("An error occurred: " + error);
                        }
                    });
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
            }
        });


        $("#edit-dialog").dialog({
            width: 320,
            autoOpen: false,
            modal: true,
            buttons: { 
                "Save and Exit":{ 
                    text: "Save and Exit",
                    class: "save-exit-button",
                    click: function() {
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

        $("#add-expert-dialog").dialog({
            width: 320,
            autoOpen: false,
            modal: true,
            buttons: {
                "Add and Exit": {
                    text: "Add and Exit",
                    class: "add-exit-button", 
                    click: function() {
                    $("#add-expert-form").submit();
                    }
                },
                "Cancel": {
                    text: "Cancel",
                    class: "cancel-button", 
                    click:function() {
                    $(this).dialog("close");
                    }
                }
            },
            classes: {
                "ui-dialog": "my-dialog",
                "ui-dialog-titlebar": "my-dialog-titlebar"
            }
        });

        $("#add-existing-expert-dialog").dialog({
            width: 320,
            autoOpen: false,
            modal: true,
            buttons: {
                "Add and Exit": {
                    text: "Add and Exit",
                    class: "add-exit-button", 
                    click: function() {
                    $("#add-existing-expert-form").submit();
                    }
                },
                "Cancel": {
                    text: "Cancel",
                    class: "cancel-button", 
                    click:function() {
                    $(this).dialog("close");
                    }
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

        $("#add-existing-expert-btn").click(function() {
            var systemId = $("#system_id").val();
            var systemName = $("#sys_name").text();
  
            $.ajax({
                url: 'get_all_experts.php',
                type: 'GET',
                data: { system_id: systemId },
                dataType: 'json',
                success: function(data) {
                    // Populate the form fields with the data retrieved from the server
                    $("#add-existing-expert-form #system_id").val(systemId);  // Set the system ID
                    $("#add-existing-expert-form #sys_name").text(systemName);  // Set the system name
                    $("#add-existing-expert-form #phone").val(data.expert.phone);  // Set the phone number

                    // Populate the dropdown with the experts list (if needed)
                    refreshAllExpertDropdown(data.expert.expert_id);

                    // Open the dialog
                    $("#add-existing-expert-dialog").dialog("open");
                },
                error: function(xhr, status, error) {
                    alert("An error occurred: " + error);
                }
            });
        });

        $("#add-expert-btn").click(function() {
            var systemId = $("#system_id").val();
            var systemName = $("#sys_name").text();
            $("#add-expert-form #system_id").val(systemId);
            $("#add-expert-form #sys_name").text(systemName);
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

        $("#add-existing-expert-form").submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: 'add_existing_expert.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    alert(response);
                    $("#add-existing-expert-dialog").dialog("close");
                    refreshExpertDropdown();
                },
                error: function(xhr, status, error) {
                    alert("An error occurred: " + error);
                }
            });
        });

        $("#assignment_expert_select").change(function() {
            var selectedExpertId = $(this).val();
            if (selectedExpertId === 'new') {
                $("#assignment_phone").val('');
            } else {
                $.ajax({
                    url: 'get_expert_details.php',
                    type: 'GET',
                    data: { expert_id: selectedExpertId },
                    dataType: 'json',
                    success: function(data) {
                        $("#assignment_phone").val(data.phone);
                    }
                });
            }
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

        $("#existing_expert_select").change(function() {
            var selectedExpertId = $(this).val();
            if (selectedExpertId === 'new') {
                $("#phone_existing").val('');
            } else {
                $.ajax({
                    url: 'get_expert_details.php',
                    type: 'GET',
                    data: { expert_id: selectedExpertId },
                    dataType: 'json',
                    success: function(data) {
                        $("#phone_existing").val(data.phone);
                    }
                });
            }
        });
    });
    </script>
</body>
</html>

