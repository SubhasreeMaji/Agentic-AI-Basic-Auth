<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$statement = $pdo->prepare('SELECT full_name, email FROM users WHERE id = :id');
$statement->execute(['id' => current_user_id()]);
$user = $statement->fetch();
if (!$user) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$errors = $_SESSION['profile_errors'] ?? [];
$formValues = $_SESSION['profile_values'] ?? $user;
unset($_SESSION['profile_errors'], $_SESSION['profile_values']);

$pageTitle = 'Edit profile';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h1>Edit profile</h1>
    <p class="intro">Update your name and email address. Password changes are handled separately.</p>
    <?php if ($errors): ?><div class="message message-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= escape($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="update-profile.php">
        <input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>">
        <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="100" autocomplete="name" required value="<?= escape((string) $formValues['full_name']) ?>"></div>
        <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= escape((string) $formValues['email']) ?>"></div>
        <div class="form-actions"><button class="button" type="submit">Save changes</button></div>
    </form>
    <div class="form-links"><a href="profile.php">Cancel and return to profile</a></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>