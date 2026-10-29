<?php
$pageTitle = $pageTitle ?? 'Basic Auth';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($pageTitle) ?> | Basic Auth</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php">Basic Auth</a>
    <nav aria-label="Main navigation">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php">Profile</a>
            <form class="nav-form" action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
                <button class="nav-link" type="submit">Log out</button>
            </form>
        <?php else: ?>
            <a href="login.php">Log in</a>
            <a href="register.php">Create account</a>
        <?php endif; ?>
    </nav>
</header>
<main class="page">