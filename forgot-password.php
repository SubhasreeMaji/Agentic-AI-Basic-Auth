<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$message = '';
$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    if (!verify_csrf_token()) {
        $errors[] = 'Your form session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = 'Enter a valid email address.';
    } else {
        $statement = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

            $pdo->beginTransaction();
            try {
                $statement = $pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id');
                $statement->execute(['user_id' => $user['id']]);
                $statement = $pdo->prepare(
                    'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)'
                );
                $statement->execute([
                    'user_id' => $user['id'],
                    'token_hash' => $tokenHash,
                    'expires_at' => $expiresAt,
                ]);
                $pdo->commit();

                if (APP_ENV === 'development') {
                    $resetUrl = APP_BASE_URL . '/reset-password.php?token=' . urlencode($token);
                    $storageDirectory = __DIR__ . '/../storage';
                    if (is_dir($storageDirectory) && is_writable($storageDirectory)) {
                        file_put_contents(
                            $storageDirectory . '/reset-links.log',
                            date(DATE_ATOM) . ' ' . $resetUrl . PHP_EOL,
                            FILE_APPEND | LOCK_EX
                        );
                    }
                }
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
        }

        $message = 'If an account matches that email address, a password reset link has been created.';
    }
}

$pageTitle = 'Forgot password';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h1>Reset your password</h1>
    <p class="intro">Enter the email address used for your account.</p>
    <?php if ($message !== ''): ?><div class="message message-success" role="status"><?= escape($message) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="message message-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="forgot-password.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= escape($email) ?>"></div>
        <div class="form-actions"><button class="button" type="submit">Request reset link</button></div>
    </form>
    <div class="form-links"><a href="login.php">Return to log in</a></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>