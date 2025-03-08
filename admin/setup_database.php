<?php
require_once '../includes/db.php';

// Read the SQL file
$sql = file_get_contents('db_setup.sql');

// Execute the SQL commands
if ($conn->multi_query($sql)) {
    echo "Database structure updated successfully!<br>";
    
    // Clear out the results
    while ($conn->more_results() && $conn->next_result()) {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }
} else {
    echo "Error updating database structure: " . $conn->error . "<br>";
}

// Close the connection
$conn->close();

echo "<br>You can now go back to <a href='manage-products.php'>manage products</a>.";
?> 