<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$errors = [];
$validToken = preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;

if ($validToken) {
    $statement = $pdo->prepare(
        'SELECT user_id FROM password_resets WHERE token_hash = :token_hash AND expires_at > CURRENT_TIMESTAMP'
    );
    $statement->execute(['token_hash' => hash('sha256', $token)]);
    $reset = $statement->fetch();
} else {
    $reset = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!verify_csrf_token()) {
        $errors[] = 'Your form session expired. Reload the page and try again.';
    } elseif (!$reset) {
        $errors[] = 'This reset link is invalid or has expired. Request a new one.';
    }
    if (strlen($password) < 12) {
        $errors[] = 'Choose a password with at least 12 characters.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The passwords do not match.';
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'SELECT user_id FROM password_resets WHERE token_hash = :token_hash AND expires_at > CURRENT_TIMESTAMP FOR UPDATE'
            );
            $statement->execute(['token_hash' => hash('sha256', $token)]);
            $reset = $statement->fetch();
            if (!$reset) {
                $pdo->rollBack();
                $errors[] = 'This reset link is invalid or has expired. Request a new one.';
            } else {
                $statement = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
                $statement->execute([
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => $reset['user_id'],
                ]);
                $statement = $pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id');
                $statement->execute(['user_id' => $reset['user_id']]);
                $pdo->commit();
                header('Location: login.php?password_reset=1');
                exit;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}

$pageTitle = 'Choose a new password';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h1>Choose a new password</h1>
    <p class="intro">Use at least 12 characters. The reset link can only be used once.</p>
    <?php if ($errors): ?><div class="message message-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if ($reset): ?>
        <form method="post" action="reset-password.php">
            <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= escape($token) ?>">
            <div class="field"><label for="password">New password</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required></div>
            <div class="field"><label for="confirm_password">Confirm new password</label><input id="confirm_password" name="confirm_password" type="password" minlength="12" autocomplete="new-password" required></div>
            <div class="form-actions"><button class="button" type="submit">Save new password</button></div>
        </form>
    <?php else: ?>
        <div class="message message-error" role="alert">This reset link is invalid or has expired. Request a new one.</div>
        <a href="forgot-password.php">Request another reset link</a>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>