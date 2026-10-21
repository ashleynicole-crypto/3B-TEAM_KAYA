<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer dashboard | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('style1.css') ?>">
</head>
<body class="portal-body">
<header class="portal-header">
    <a href="<?= site_url('/') ?>"><strong>Sun Son Solar</strong></a>
    <span>Welcome, <?= esc($profile['first_name']) ?></span>
    <a href="<?= site_url('logout') ?>">Log out</a>
</header>
<main class="portal-main">
    <h1>Your solar service dashboard</h1>
    <?php if ($msg = session()->getFlashdata('message')): ?><p class="notice"><?= esc($msg) ?></p><?php endif ?>
    <?php if ($msg = session()->getFlashdata('error')): ?><p class="notice notice--error"><?= esc($msg) ?></p><?php endif ?>

    <div class="portal-grid">
        <section class="portal-card">
            <h2>Request a service</h2>
            <form method="post" action="<?= site_url('booking') ?>">
                <?= csrf_field() ?>
                <label>Service<select name="service_id" required><?php foreach ($services as $service): ?><option value="<?= (int) $service['service_id'] ?>"><?= esc($service['service_name']) ?><?= $service['base_price'] !== null ? ' · ₱'.number_format((float) $service['base_price'], 2) : '' ?></option><?php endforeach ?></select></label>
                <label>Preferred date and time<input type="datetime-local" name="scheduled_at" required></label>
                <label>Notes<textarea name="customer_notes" rows="3"></textarea></label>
                <button class="auth-submit">Request appointment</button>
            </form>
        </section>

        <section class="portal-card portal-card--wide">
            <h2>Products and free quotes</h2>
            <div class="benefit-grid">
                <?php foreach ($products as $product): ?><article class="list-row"><img class="product-image" src="<?= esc(product_image_url($product)) ?>" alt="<?= esc($product['product_name']) ?>" loading="lazy"><h3><?= esc($product['product_name']) ?></h3><p><?= esc($product['description']) ?></p><?php if ($product['price']!==null): ?><p>From ₱<?= number_format((float)$product['price'],2) ?></p><?php endif ?><form method="post" action="<?= site_url('quote') ?>"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>"><label>What would you like to know?<textarea name="details" maxlength="4000" rows="2"></textarea></label><button class="small-button">Request a free quote</button></form></article><?php endforeach ?>
            </div>
            <?php if (!$products): ?><p>Product details will be available soon.</p><?php endif ?>
        </section>

        <section class="portal-card">
            <h2>Notifications and updates</h2>
            <?php foreach ($notifications as $item): ?><article class="list-row"><strong><?= esc($item['title']) ?></strong><p><?= esc($item['message']) ?></p><small><?= esc($item['created_at']) ?></small></article><?php endforeach ?>
            <?php foreach ($announcements as $item): ?><article class="list-row"><strong><?= esc($item['title']) ?></strong><p><?= esc($item['message']) ?></p></article><?php endforeach ?>
            <?php if (!$notifications && !$announcements): ?><p>No updates yet.</p><?php endif ?>
        </section>

        <section class="portal-card portal-card--wide">
            <h2>Upcoming and requested services</h2>
            <?php $upcoming = array_filter($bookings, fn ($b) => !in_array($b['status'], ['completed', 'cancelled', 'declined'], true)); ?>
            <?php if (!$upcoming): ?><p>No upcoming services. Request an appointment to get started.</p><?php else: ?>
                <div class="table-wrap"><table class="booking-table"><thead><tr><th>Service</th><th>When</th><th>Status</th><th>Technician</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($upcoming as $booking): ?><tr><td><?= esc($booking['service_name']) ?></td><td><?= esc($booking['scheduled_at']) ?><?php if ($booking['reschedule_requested_at']): ?><br><small>Reschedule requested: <?= esc($booking['reschedule_requested_at']) ?></small><?php endif ?></td><td><span class="status-pill"><?= esc(str_replace('_', ' ', $booking['status'])) ?></span></td><td><?= esc(trim(($booking['tech_first'] ?? '').' '.($booking['tech_last'] ?? '')) ?: 'To be assigned') ?></td><td><?php if (in_array($booking['status'], ['approved','scheduled'], true)): ?><form method="post" action="<?= site_url('booking/'.(int)$booking['booking_id'].'/reschedule') ?>"><?= csrf_field() ?><label>Request new time<input type="datetime-local" name="requested_at" required></label><button class="small-button">Request reschedule</button></form><?php endif ?><?php if (in_array($booking['status'], ['requested','approved','scheduled'], true)): ?><form method="post" action="<?= site_url('booking/'.(int) $booking['booking_id'].'/cancel') ?>"><?= csrf_field() ?><button class="small-button">Cancel request</button></form><?php endif ?></td></tr><?php endforeach ?>
                </tbody></table></div>
            <?php endif ?>
        </section>

        <section class="portal-card">
            <h2>Service history and receipts</h2>
            <?php if (!$history): ?><p>Completed services and payments will appear here.</p><?php else: foreach ($history as $item): ?>
                <article class="list-row"><strong><?= esc($item['service_name']) ?> · <?= esc($item['scheduled_at']) ?></strong><p><?= esc($item['status']) ?> · <?php if ($item['amount'] !== null): ?>₱<?= number_format((float) $item['amount'], 2) ?> (<?= esc($item['payment_status']) ?>)<?php else: ?>No payment recorded yet<?php endif ?></p><?php if ($item['reference_number']): ?><small>Receipt/reference <?= esc($item['reference_number']) ?></small><?php endif ?></article>
            <?php endforeach; endif ?>
        </section>

        <section class="portal-card">
            <h2>Contact support</h2>
            <form method="post" action="<?= site_url('support') ?>"><?= csrf_field() ?><label>Subject<input name="subject" maxlength="160" required></label><label>How can we help?<textarea name="message" rows="3" required></textarea></label><button class="auth-submit">Send support request</button></form>
            <?php foreach ($tickets as $ticket): ?><article class="list-row"><strong><?= esc($ticket['ticket_type']==='quote'?'Quote request':'Support request') ?>: <?= esc($ticket['subject']) ?></strong> · <?= esc($ticket['status']) ?><p><?= esc($ticket['message']) ?></p><?php if ($ticket['response']): ?><p><strong>Team response:</strong><br><?= nl2br(esc($ticket['response'])) ?></p><?php endif ?></article><?php endforeach ?>
        </section>

        <section class="portal-card">
            <h2>Share your experience</h2>
            <p>Your story stays private until you choose to allow publication and an administrator approves it.</p>
            <form method="post" action="<?= site_url('testimonial') ?>"><?= csrf_field() ?><label>Your story<textarea name="quote" rows="4" maxlength="2000" required></textarea></label><label class="consent-label"><input type="checkbox" name="consent_to_publish" value="1" required> I agree that Sun Son Solar may publish this story after review.</label><button class="small-button">Submit for review</button></form>
            <?php foreach ($testimonials as $story): ?><p class="list-row">Story submitted <?= esc($story['created_at']) ?> · <?= esc(ucfirst($story['status'])) ?></p><?php endforeach ?>
        </section>

        <section class="portal-card portal-card--wide">
            <h2>Your profile</h2>
            <form class="profile-form" method="post" action="<?= site_url('profile') ?>">
                <?= csrf_field() ?>
                <div class="signup-grid">
                    <?php foreach (['first_name'=>'First name','middle_name'=>'Middle name','last_name'=>'Last name','birthdate'=>'Birthdate','gender'=>'Gender','email'=>'Email','phone_number'=>'Phone','address'=>'Address','username'=>'Username'] as $key => $label): ?>
                        <label><?= esc($label) ?><?php if ($key === 'gender'): ?><select name="gender"><option <?= $profile['gender'] === 'Female' ? 'selected' : '' ?>>Female</option><option <?= $profile['gender'] === 'Male' ? 'selected' : '' ?>>Male</option><option <?= $profile['gender'] === 'Other' ? 'selected' : '' ?>>Other</option></select><?php elseif ($key === 'address'): ?><textarea name="address" required><?= esc($profile[$key]) ?></textarea><?php else: ?><input name="<?= esc($key) ?>" value="<?= esc($profile[$key]) ?>" <?= $key === 'birthdate' ? 'type="date"' : ($key === 'email' ? 'type="email"' : '') ?> required><?php endif ?></label>
                    <?php endforeach ?>
                </div>
                <label class="consent-label"><input type="checkbox" name="promotional_opt_in" value="1" <?= $profile['promotional_opt_in'] ? 'checked' : '' ?>> Receive promotional offers in my account notifications</label>
                <h3>Change password (optional)</h3>
                <div class="signup-grid"><label>Current password<input type="password" name="current_password" autocomplete="current-password"></label><label>New password, at least 12 characters<input type="password" name="new_password" autocomplete="new-password" minlength="12"></label><label>Confirm new password<input type="password" name="confirm_new_password" autocomplete="new-password" minlength="12"></label></div>
                <button class="auth-submit">Save profile</button>
            </form>
        </section>
    </div>
</main>
</body>
</html>
