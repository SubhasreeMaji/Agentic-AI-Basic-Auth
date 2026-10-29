<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
header('Location: ' . (isset($_SESSION['user_id']) ? 'profile.php' : 'login.php'));
exit;