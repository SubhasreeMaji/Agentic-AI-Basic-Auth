<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_login();

$statement = $pdo->prepare('SELECT full_name, email, created_at FROM users WHERE id = :id');
$statement->execute(['id' => current_user_id()]);
$user = $statement->fetch();

if (!$user) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$pageTitle = 'Your profile';
require __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <h1>Your profile</h1>
    <p class="intro">Account details and membership information.</p>
    <?php if (isset($_GET['updated'])): ?><div class="message message-success" role="status">Your profile was updated successfully.</div><?php endif; ?>
    <?php if (isset($_GET['password_reset'])): ?><div class="message message-success" role="status">Your password was changed successfully.</div><?php endif; ?>
    <dl class="profile-data">
        <div><dt>Full name</dt><dd><?= escape($user['full_name']) ?></dd></div>
        <div><dt>Email address</dt><dd><?= escape($user['email']) ?></dd></div>
        <div><dt>Account created</dt><dd><?= escape($user['created_at']) ?></dd></div>
    </dl>
    <div class="actions"><a class="button" href="edit-profile.php">Edit profile</a>
        <form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= escape(csrf_token()) ?>"><button class="button button-secondary" type="submit">Log out</button></form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>