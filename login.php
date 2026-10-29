<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

$errors = [];
$email = '';
$successMessage = '';
if (isset($_GET['registered'])) {
    $successMessage = 'Your account was created. You can log in now.';
} elseif (isset($_GET['password_reset'])) {
    $successMessage = 'Your password was changed. You can log in with the new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf_token()) {
        $errors[] = 'Your form session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors[] = 'The email or password is incorrect.';
    } else {
        $statement = $pdo->prepare('SELECT id, full_name, password FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'The email or password is incorrect.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            unset($_SESSION['csrf_token']);
            header('Location: profile.php');
            exit;
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h1>Welcome back</h1>
    <p class="intro">Log in to view your account.</p>
    <?php if ($successMessage !== ''): ?><div class="message message-success" role="status"><?= escape($successMessage) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="message message-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= escape($email) ?>"></div>
        <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
        <div class="form-actions"><button class="button" type="submit">Log in</button></div>
    </form>
    <div class="form-links"><a href="forgot-password.php">Forgot password?</a><a href="register.php">Create an account</a></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>