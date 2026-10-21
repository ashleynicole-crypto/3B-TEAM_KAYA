<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Employee portal | Sun Son Solar</title><link rel="stylesheet" href="<?= base_url('style1.css') ?>"></head>
<body class="portal-body">
<header class="portal-header"><strong>Sun Son Solar · Team updates</strong><span><?= esc(session('username')) ?></span><a href="<?= site_url('staff/profile') ?>">Profile</a><a href="<?= site_url('staff/password') ?>">Change password</a><a href="<?= site_url('logout') ?>">Log out</a></header>
<main class="portal-main"><h1>Employee portal</h1><p>Company announcements and team updates for <?= esc(session('username')) ?>.</p><h2 class="portal-section-title">Announcements</h2>
<div class="announcement-list">
    <?php if (!$announcements): ?><section class="portal-card announcement-card"><p>No announcements yet.</p></section><?php endif ?>
    <?php foreach ($announcements as $item): ?><article class="portal-card announcement-card"><h2><?= esc($item['title']) ?></h2><p><?= esc($item['message']) ?></p><small><?= esc($item['publish_at']) ?></small></article><?php endforeach ?>
</div>
</main></body></html>
