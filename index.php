<?php
include 'dbConnection.php';

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

// Function to get the assigned expert for each system
function getAssignedExpert($system_id, $conn) {
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
    return $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Telephone List Management</title>
    <style>
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
            <label for="system_name">System:</label>
            <input type="text" id="system_name" name="system_name" readonly><br>
            <label for="expert_name">Current Expert:</label>
            <input type="text" id="expert_name" name="expert_name" readonly><br>
            <label for="expert_select">Select New Expert:</label>
            <select id="expert_select" name="expert_id">
                <!-- Options will be filled by jQuery -->
            </select><br>
            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" pattern="^\+?\d*$" placeholder="Enter phone number"><br>
            <input type="hidden" id="system_id" name="system_id">
            <button type="button" id="save-only-btn">Save</button>
            <button type="button" id="add-expert-btn">Add New Expert</button>
        </form>
    </div>

    <!-- Add New Expert Dialog -->
    <div id="add-expert-dialog" title="Add New Expert" style="display:none;">
        <form id="add-expert-form">
            <label for="new_expert_name">Expert Name:</label>
            <input type="text" id="new_expert_name" name="new_expert_name" required><br>
            <label for="new_expert_phone">Phone:</label>
            <input type="tel" id="new_expert_phone" name="new_expert_phone" pattern="^\+?\d*$" required><br>
            <button type="submit">Add Expert</button>
        </form>
    </div>

    <script>
        $(document).ready(function() {
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
                                location.reload(); // Reload the page to reflect changes
                            }
                        });
                    },
                    "Cancel": function() {
                        $(this).dialog("close");
                    }
                }
            });

            $("#add-expert-dialog").dialog({
                autoOpen: false,
                modal: true,
                buttons: {
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

                        var $expertSelect = $("#expert_select");
                        $expertSelect.empty(); // Clear existing options
                        $expertSelect.append('<option value="new">Add New Expert</option>'); // Option to add new expert

                        var selectedExpertId = data.expert.expert_id;

                        // Populate the dropdown with experts
                        $.each(data.experts, function(index, expert) {
                            $expertSelect.append(
                                $('<option>', { value: expert.Id, text: expert.name })
                            );
                        });

                        // Set the dropdown value to the current expert's ID
                        $expertSelect.val(selectedExpertId);

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
                        alert(response);
                        // The dialog remains open; only the data is saved
                    }
                });
            });

            $("#add-expert-btn").click(function() {
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
                        location.reload(); // Reload the page to reflect changes
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
                        url: 'get_experts.php',
                        type: 'GET',
                        data: {
                            system_id: $("#system_id").val(),
                            expert_id: selectedExpertId
                        },
                        dataType: 'json',
                        success: function(data) {
                            $("#expert_name").val(data.specific_expert.name);
                            $("#phone").val(data.specific_expert.Private_phone);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>


