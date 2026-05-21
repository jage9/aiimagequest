<?php
session_start();

if (isset($_SESSION['admin_authenticated'])) {
    header('Location: add_image.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    if (ADMIN_PASSWORD_HASH && password_verify($_POST['password'], ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: add_image.php');
        exit;
    }
    $error = 'Invalid password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | AI Image Quest</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<main id="main-content">
    <h1>Admin Login</h1>
    <?php if ($error): ?>
        <p style="color:red"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post">
        <p>
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required autofocus>
        </p>
        <button type="submit">Login</button>
    </form>
</main>
</body>
</html>
