<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Employee profile | Sun Son Solar</title><link rel="stylesheet" href="<?= base_url('style1.css') ?>"></head>
<body class="portal-body">
<header class="portal-header"><a href="<?= site_url('staff') ?>">← Employee portal</a><strong>Profile and account</strong><a href="<?= site_url('logout') ?>">Log out</a></header>
<main class="portal-main portal-main--narrow"><h1>Your profile</h1><section class="portal-card">
    <?php if ($message=session()->getFlashdata('message')): ?><p class="notice"><?= esc($message) ?></p><?php endif ?>
    <?php if ($error=session()->getFlashdata('error')): ?><p class="notice notice--error"><?= esc($error) ?></p><?php endif ?>
    <p><?= esc(trim($employee['first_name'].' '.$employee['middle_name'].' '.$employee['last_name'])) ?> · <?= esc($employee['username']) ?></p>
    <form class="employee-profile-form" method="post" action="<?= site_url('staff/profile') ?>"><?= csrf_field() ?><div class="signup-grid"><label>Email<input type="email" name="email" value="<?= esc($employee['email']) ?>" maxlength="255" required></label><label>Phone<input name="phone_number" value="<?= esc($employee['phone_number']) ?>" maxlength="20" required></label><label class="signup-field-wide">Address<textarea name="address" maxlength="255" required><?= esc($employee['address']) ?></textarea></label></div><button class="auth-submit">Save profile</button></form>
    <p><a href="<?= site_url('staff/password') ?>">Change password</a></p>
</section></main>
</body></html>
