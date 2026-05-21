<?php
// /home/aiimagequest/public_html/db_connect.php

// Include the secure configuration file
require_once dirname(__DIR__) . '/config.php';

function get_db_connection() {
    // Suppress default warnings/errors with @
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        // Log the error to the server's error log
        error_log("Database Connection Failed: " . $conn->connect_error);
        // Stop the script and return a generic failure message or null
        die("A database error occurred. Please try again later.");
        // Or for functions that expect an object: return null;
    }

    // Set character set
    $conn->set_charset("utf8mb4");

    return $conn;
}
?>