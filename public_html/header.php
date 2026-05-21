<?php
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php if (isset($pageTitle)) { echo htmlspecialchars($pageTitle) . " | "; } ?>AI Image Quest</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <header class="site-header">
        <nav class="main-nav">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="images.php">Images</a></li>
                <li><a href="contribute.php">Contribute</a></li>
                <li><a href="about.php">About</a></li>
            </ul>
        </nav>
    </header>