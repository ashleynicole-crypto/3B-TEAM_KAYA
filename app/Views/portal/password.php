<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change temporary password | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('style1.css') ?>">
</head>
<body class="portal-body">
    <main class="portal-main portal-main--compact">
        <section class="portal-card">
            <h1>Update your password</h1>
            <p><?= ($accountType ?? 'employee') === 'customer' ? 'Your temporary password must be changed before you can use your account.' : 'This temporary employee password must be changed before you can use your portal.' ?></p>
            <?php if ($message = session()->getFlashdata('error')): ?><p class="notice notice--error" role="alert"><?= esc($message) ?></p><?php endif ?>
            <form method="post" action="<?= site_url(($accountType ?? 'employee') === 'customer' ? 'dashboard/password' : 'staff/password') ?>">
                <?= csrf_field() ?>
                <label>Current temporary password<input type="password" name="current_password" autocomplete="current-password" required></label>
                <label>New password (at least 12 characters, with a letter and number)<input type="password" name="new_password" autocomplete="new-password" minlength="12" required></label>
                <label>Confirm new password<input type="password" name="confirm_password" autocomplete="new-password" minlength="12" required></label>
                <button class="auth-submit">Change password and continue</button>
            </form>
            <p><a href="<?= site_url('logout') ?>">Log out</a></p>
        </section>
    </main>
</body>
</html>
