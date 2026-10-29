<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

$errors = [];
$fullName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!verify_csrf_token()) {
        $errors[] = 'Your form session expired. Please try again.';
    }
    if ($fullName === '' || mb_strlen($fullName) > 100) {
        $errors[] = 'Enter your name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 12) {
        $errors[] = 'Choose a password with at least 12 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    if (!$errors) {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);
        if ($statement->fetch()) {
            $errors[] = 'An account with that email address already exists.';
        } else {
            try {
                $statement = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password) VALUES (:full_name, :email, :password)'
                );
                $statement->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                header('Location: login.php?registered=1');
                exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $errors[] = 'An account with that email address already exists.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h1>Create your account</h1>
    <p class="intro">Register with your name and email address.</p>
    <?php if ($errors): ?>
        <div class="message message-error" role="alert"><ul>
            <?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <form method="post" action="register.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="100" autocomplete="name" required value="<?= escape($fullName) ?>"></div>
        <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= escape($email) ?>"></div>
        <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required></div>
        <div class="field"><label for="confirm_password">Confirm password</label><input id="confirm_password" name="confirm_password" type="password" minlength="12" autocomplete="new-password" required></div>
        <div class="form-actions"><button class="button" type="submit">Create account</button></div>
    </form>
    <div class="form-links"><a href="login.php">Already have an account? Log in</a></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>