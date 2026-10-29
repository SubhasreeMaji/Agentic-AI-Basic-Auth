<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit-profile.php');
    exit;
}

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$errors = [];

if (!verify_csrf_token()) {
    $errors[] = 'Your form session expired. Please try again.';
}
if ($fullName === '' || mb_strlen($fullName) > 100) {
    $errors[] = 'Enter your name (up to 100 characters).';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    $errors[] = 'Enter a valid email address.';
}

if (!$errors) {
    $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id');
    $statement->execute(['email' => $email, 'id' => current_user_id()]);
    if ($statement->fetch()) {
        $errors[] = 'That email address is already associated with another account.';
    }
}

if ($errors) {
    $_SESSION['profile_errors'] = $errors;
    $_SESSION['profile_values'] = ['full_name' => $fullName, 'email' => $email];
    header('Location: edit-profile.php');
    exit;
}

try {
    $statement = $pdo->prepare(
        'UPDATE users SET full_name = :full_name, email = :email, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
    );
    $statement->execute(['full_name' => $fullName, 'email' => $email, 'id' => current_user_id()]);
    $_SESSION['full_name'] = $fullName;
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $_SESSION['profile_errors'] = ['That email address is already associated with another account.'];
        $_SESSION['profile_values'] = ['full_name' => $fullName, 'email' => $email];
        header('Location: edit-profile.php');
        exit;
    }
    throw $exception;
}

header('Location: profile.php?updated=1');
exit;