<?php
require_once dirname(__DIR__) . '/config.php';

function get_db_connection(): mysqli
{
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    if ($conn->connect_error) {
        error_log('Database Connection Failed: ' . $conn->connect_error);
        die('A database error occurred. Please try again later.');
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}
?>