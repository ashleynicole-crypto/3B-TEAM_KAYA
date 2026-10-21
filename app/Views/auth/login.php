<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('style1.css') ?>">
</head>
<body class="auth-split-body login-body">
    <main class="auth-split-page login-page">
        <div class="auth-photo login-photo">
            <img class="side-logo login-side-logo" src="<?= base_url('PCTURES/sunsun%20logo.png') ?>" alt="Sun Son Solar">
        </div>

        <section class="auth-pane login-pane">
            <p class="auth-welcome">Welcome back!</p>
            <div class="auth-card login-card">
                <h1>Log in</h1>

                <?php if (session()->getFlashdata('message')): ?>
                    <p><?= esc(session()->getFlashdata('message')) ?></p>
                <?php endif ?>

                <?php if (! empty($errors)): ?>
                    <p role="alert"><?= esc(implode(' ', $errors)) ?></p>
                <?php endif ?>

                <form class="auth-form" method="post" action="<?= site_url('login') ?>">
                    <?= csrf_field() ?>
                    <div class="auth-field">
                        <label for="username">Username</label>
                        <input id="username" name="username" autocomplete="username" required>
                    </div>
                    <div class="auth-field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <button class="auth-submit">Log in</button>
                </form>

                <p class="auth-switch">New here? <a href="<?= site_url('register') ?>">Create a customer account</a></p>
            </div>
        </section>
    </main>
</body>
</html>
