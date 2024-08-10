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
    <title>Telephone List Management</title>
    <style>
        #assign-experts-button {
            background-color: #008CBA; /* Blue */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        #assign-experts-button:hover {
            background-color: #007bb5; /* Darker blue */
        }
        .edit-button, .add-expert-button {
            background-color: #4CAF50; /* Green */
            border: none;
            color: white;
            padding: 5px 10px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
        }
        .edit-button:active {
            background-color: #45a049; /* Darker green */
        }
        .add-expert-button {
            background-color: #008CBA; /* Blue */
        }
        .add-expert-button:active {
            background-color: #007bb5; /* Darker blue */
        }
        #current_expert, #sys_name {
            font-weight: bold;
        }
    </style>
    <!-- jQuery and jQuery UI -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
</head>
<body>
    <h1>Telephone List Management</h1>
    <h2>Assign Experts to Systems</h2>
    <h2>Current Schedules</h2>
    <button id="assign-experts-button">Assign Experts Randomly</button>


    <table border="1">
        <tr>
            <th>System</th>
            <th>Expert</th>
            <th>Phone</th>
            <th></th>
        </tr>
        <?php while ($system = $systems->fetch_assoc()): 
            $expert = getAssignedExpert($system['System_id'], $conn);
        ?>
            <tr>
                <td><?= htmlspecialchars($expert['system_name']) ?></td>
                <td><?= htmlspecialchars($expert['expert_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($expert['phone'] ?? 'N/A') ?></td>
                <td>
                    <a href="#" class="edit-button" data-system_id="<?= htmlspecialchars($system['System_id']) ?>">Edit</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- Edit Dialog -->
    <div id="edit-dialog" title="Edit Expert Assignment" style="display:none;">
        <form id="edit-form">
            <label for="system_name">System:</label><br>
            <span id="sys_name"></span><br>
            <label for="expert_name">Current Expert:</label><br>
            <span id="current_expert"></span><br>
            <label for="expert_select">Select New Expert:</label>
            <select id="expert_select" name="expert_id">
            </select><br>
            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" pattern="^\+?\d*$" placeholder="Enter phone number"><br>
            <input type="hidden" id="system_id" name="system_id">
            <button type="button" id="add-expert-btn">Add New Expert</button>
            <button type="button" id="save-only-btn">Save</button>
        </form>
    </div>

    <!-- Add New Expert Dialog -->
    <div id="add-expert-dialog" title="Add New Expert" style="display:none;">
        <form id="add-expert-form">
            <input type="hidden" id="system_id" name="system_id">
            <label for="new_expert_name">Expert Name:</label>
            <input type="text" id="new_expert_name" name="new_expert_name" required><br>
            <label for="new_expert_phone">Phone:</label>
            <input type="tel" id="new_expert_phone" name="new_expert_phone" pattern="^\+?\d*$" required><br>
            <button type="button" id="save-and-add">Add Expert</button>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        $("#assign-experts-button").click(function() {
            $.ajax({
                url: 'assign_experts.php', // PHP script to handle the expert assignment
                type: 'GET',
                success: function(response) {
                    alert(response); // Show success message
                    location.reload(); // Reload the page to reflect changes
                },
                error: function(xhr, status, error) {
                    alert("An error occurred: " + error); // Show error message
                }
            });
        });
        function refreshExpertDropdown(selectedExpertId) {
            var systemId = $("#system_id").val(); // Get the current system ID

            $.ajax({
                url: 'get_experts.php',
                type: 'GET',
                data: { system_id: systemId },
                dataType: 'json',
                success: function(data) {
                    var $expertSelect = $("#expert_select");
                    $expertSelect.empty(); // Clear existing options

                    // Populate the dropdown with experts
                    $.each(data.experts, function(index, expert) {
                        $expertSelect.append(
                            $('<option>', { value: expert.Id, text: expert.name })
                        );
                    });

                    // Set the dropdown value to the selected expert's ID, if provided
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
                            refreshExpertDropdown(); // Refresh the dropdown list after saving
                            location.reload(); // Reload the page to reflect changes
                        }
                    });
                },
                "Cancel": function() {
                    $(this).dialog("close");
                    location.reload(); // Reload the page to reflect changes
                    }
                },
                close: function(event, ui) {
                    var systemId = $("#system_id").val();
                    $.ajax({
                        url: 'get_experts.php', // Create a separate PHP file to fetch the updated details
                        type: 'GET',
                        data: { system_id: systemId },
                        success: function(response) {
                            location.reload(); // Reload the page to reflect changes
                        }
                    });
                }
            });

        $("#add-expert-dialog").dialog({
            autoOpen: false,
            modal: true,
            buttons: {
                "Add and Exit": function() {
                    $("#add-expert-form").submit(); // Submit the form and handle closing in the form's success callback
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
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
                    $("#system_name").val(data.expert.system_name);
                    $("#phone").val(data.expert.phone);
                    $("#system_id").val(systemId);

                    var selectedExpertId = data.expert.expert_id;

                    console.log("Selected Expert ID:", selectedExpertId);

                    refreshExpertDropdown(selectedExpertId);

                    // Set the dropdown value to the current expert's ID
                    $("#expert_select").val(selectedExpertId);

                    // sets Name of the system and current expert
                    $("#current_expert").text(data.expert.expert_name);
                    $("#sys_name").text(data.expert.system_name);

                    // Update the current expert name field
                    $("#expert_name").val(data.expert.expert_name);

                    // Open the edit dialog
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
                    if (response.trim() === "Expert assignment and phone number updated successfully.") { // Assuming "Success" is the expected response for a successful save
                        alert("Expert details updated successfully.");
                        
                        // Update the #current_expert span with the new expert's name
                        var newExpertName = $("#expert_select option:selected").text();
                        $("#current_expert").text(newExpertName);

                        // Optionally, refresh form fields with the latest data from the server
                        $.ajax({
                            url: 'get_expert_details.php',
                            type: 'GET',
                            data: { system_id: $("#system_id").val() },
                            dataType: 'json',
                            success: function(data) {
                                $("#system_name").val(data.expert.system_name);
                                $("#phone").val(data.expert.phone);
                                $("#expert_select").val(data.expert.expert_id); // Set dropdown to the expert's ID
                                $("#expert_name").val(data.expert.expert_name);
                            }
                        });
                    } else {
                        // Display the error message returned from the server
                        alert("Error: " + response);
                        
                        // Optionally, do not update the #current_expert span or form fields
                    }
                },
                error: function(xhr, status, error) {
                    alert("An unexpected error occurred: " + error);
                }
            });
        });

        $("#add-expert-btn").click(function() {
            var systemId = $("#system_id").val(); // Ensure this retrieves the correct system_id
            $("#add-expert-form #system_id").val(systemId); // Set it in the add-expert-form
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
                    // Optionally, you might want to refresh the expert list or do other actions here
                    // Refresh the expert dropdown in the edit dialog
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
                    refreshExpertDropdown(); // Refresh the expert list in the #edit-dialog
                    // Keep the dialog open for adding more experts
                }
            });
        });

        $("#expert_select").change(function() {
            var selectedExpertId = $(this).val();
            if (selectedExpertId === 'new') {
                $("#expert_name").prop('readonly', false); // Allow editing if "Add New Expert" is selected
                $("#phone").val(''); // Clear phone field for new expert
            } else {
                $("#expert_name").prop('readonly', true); // Prevent editing if an existing expert is selected
                $.ajax({
                    url: 'get_expert_details.php', // New PHP file to fetch specific expert details
                    type: 'GET',
                    data: {
                        expert_id: selectedExpertId
                    },
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
