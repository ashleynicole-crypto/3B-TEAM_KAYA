<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff dashboard | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('style1.css') ?>">
</head>
<body class="portal-body">
    <header class="portal-header">
        <strong>Sun Son Solar · <?= esc(ucfirst($role)) ?> dashboard</strong>
        <span><?= esc(session('username')) ?></span>
        <a href="<?= site_url('staff/profile') ?>">Profile</a>
        <a href="<?= site_url('staff/password') ?>">Change password</a>
        <a href="<?= site_url('logout') ?>">Log out</a>
    </header>

    <main class="portal-main">
        <h1>Operations overview</h1>

        <?php if ($msg = session()->getFlashdata('message')): ?>
            <p class="notice"><?= esc($msg) ?></p>
        <?php endif ?>
        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p class="notice notice--error"><?= esc($msg) ?></p>
        <?php endif ?>

        <div class="metric-grid">
            <article>
                <strong><?= (int) $counts['customers'] ?></strong>
                <span>Customers</span>
            </article>
            <article>
                <strong><?= (int) $counts['bookings'] ?></strong>
                <span>Bookings</span>
            </article>
            <article><strong><?= (int) $counts['pending'] ?></strong><span>Pending requests</span></article>
            <article><strong><?= (int) $counts['confirmed'] ?></strong><span>Confirmed appointments</span></article>
            <article><strong><?= (int) $counts['completed'] ?></strong><span>Completed</span></article>
            <article><strong><?= (int) $counts['cancelled'] ?></strong><span>Cancelled</span></article>
            <article><strong><?= (int) $counts['employees'] ?></strong><span>Active employees</span></article>
            <article><strong><?= (int) $counts['attendance'] ?></strong><span>Check-ins today</span></article>
            <?php if ($role==='admin'): ?><article>
                <strong>₱<?= number_format((float) $counts['revenue'], 2) ?></strong>
                <span>Recorded revenue</span>
            </article><?php endif ?>
        </div>

        <?php if (in_array($role, ['admin', 'hr'], true)): ?>
            <nav class="staff-links">
                <a href="<?= site_url('staff/users') ?>">Register and manage employees</a>
            </nav>
        <?php endif ?>

        <section class="portal-card" id="schedule-calendar">
            <div class="calendar-toolbar">
                <a class="small-button" href="<?= site_url('staff') ?>?month=<?= esc($calendarMonth->modify('-1 month')->format('Y-m')) ?>">Previous</a>
                <h2><?= esc($calendarMonth->format('F Y')) ?> schedule</h2>
                <a class="small-button" href="<?= site_url('staff') ?>?month=<?= esc($calendarMonth->modify('+1 month')->format('Y-m')) ?>">Next</a>
            </div>
            <div class="calendar-grid" role="grid" aria-label="Monthly booking schedule">
                <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $weekday): ?><div class="calendar-weekday" role="columnheader"><?= esc($weekday) ?></div><?php endforeach ?>
                <?php foreach ($calendarDays as $calendarDay): ?>
                    <section class="calendar-day <?= $calendarDay['inMonth'] ? '' : 'calendar-day--outside' ?>" role="gridcell">
                        <time datetime="<?= esc($calendarDay['date']->format('Y-m-d')) ?>"><?= esc($calendarDay['date']->format('j')) ?></time>
                        <?php foreach ($calendarDay['bookings'] as $calendarBooking): ?>
                            <p><strong><?= esc(substr($calendarBooking['scheduled_at'], 11, 5)) ?></strong> <?= esc($calendarBooking['service_name']) ?><br><small><?= esc($calendarBooking['first_name'].' '.$calendarBooking['last_name']) ?> · <?= esc(str_replace('_',' ',$calendarBooking['status'])) ?></small></p>
                        <?php endforeach ?>
                    </section>
                <?php endforeach ?>
            </div>
        </section>

        <section class="portal-card">
            <h2>Schedule overview and dispatch</h2>
            <div class="table-wrap">
                <table class="dispatch-table">
                    <thead>
                        <tr>
                            <th>Customer / site</th>
                            <th>Service</th>
                            <th>Schedule</th>
                            <th>Update / assign</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <?= esc($b['first_name'] . ' ' . $b['last_name']) ?><br>
                                    <small><?= esc($b['address']) ?> · <?= esc($b['phone_number']) ?></small>
                                </td>
                                <td><?= esc($b['service_name']) ?></td>
                                <td>
                                    <?= esc($b['scheduled_at']) ?><br>
                                    <span class="status-pill"><?= esc($b['status']) ?></span>
                                </td>
                                <td>
                                    <?php if (in_array($role, ['admin', 'dispatcher'], true)): ?>
                                        <form method="post" action="<?= site_url('staff/booking/' . (int) $b['booking_id']) ?>">
                                            <?= csrf_field() ?>
                                            <select name="status">
                                                <?php foreach (['requested', 'approved', 'declined', 'scheduled', 'in_progress', 'completed', 'cancelled'] as $s): ?>
                                                    <option value="<?= $s ?>" <?= $s === $b['status'] ? 'selected' : '' ?>>
                                                        <?= esc(ucwords(str_replace('_', ' ', $s))) ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                            <select name="assigned_technician_id">
                                                <option value="">Unassigned</option>
                                                <?php foreach ($technicians as $tech): ?>
                                                    <option
                                                        value="<?= (int) $tech['employee_id'] ?>"
                                                        <?= (int) $tech['employee_id'] === (int) $b['assigned_technician_id'] ? 'selected' : '' ?>
                                                    >
                                                        <?= esc($tech['first_name'] . ' ' . $tech['last_name']) ?>
                                                    </option>
                                                <?php endforeach ?>
                                            </select>
                                            <input
                                                type="datetime-local"
                                                name="scheduled_at"
                                                value="<?= esc(date('Y-m-d\TH:i', strtotime($b['reschedule_requested_at'] ?: $b['scheduled_at']))) ?>"
                                            >
                                            <button class="small-button">Save</button>
                                        </form>
                                    <?php else: ?>
                                        <?= esc(trim(($b['tech_first'] ?? '') . ' ' . ($b['tech_last'] ?? '')) ?: 'Unassigned') ?>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="portal-grid">
            <section class="portal-card">
                <h2>Customer support tickets</h2>
                <?php foreach ($tickets as $t): ?>
                    <article class="list-row">
                        <strong><?= esc($t['subject']) ?></strong>
                        <p><?= esc($t['first_name'] . ' ' . $t['last_name']) ?> · <?= esc($t['status']) ?></p>
                        <p><?= esc($t['message']) ?></p>
                        <?php if ($t['response']): ?><p><strong>Previous response:</strong><br><?= nl2br(esc($t['response'])) ?></p><?php endif ?>
                        <p><small>Assigned to: <?= esc(trim(($t['assignee_first']??'').' '.($t['assignee_last']??'')) ?: 'Unassigned') ?></small></p>
                        <?php if (in_array($role, ['admin', 'dispatcher'], true)): ?>
                            <form method="post" action="<?= site_url('staff/ticket/' . (int) $t['ticket_id']) ?>">
                                <?= csrf_field() ?>
                                <label>Assign to<select name="assigned_employee_id"><option value="0">Unassigned</option><?php foreach($supportAgents as $agent): ?><option value="<?= (int)$agent['employee_id'] ?>" <?= (int)$t['assigned_employee_id']===(int)$agent['employee_id']?'selected':'' ?>><?= esc($agent['first_name'].' '.$agent['last_name'].' · '.ucfirst($agent['role'])) ?></option><?php endforeach ?></select></label>
                                <select name="status">
                                    <?php foreach (['open', 'in_progress', 'resolved', 'closed'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $s === $t['status'] ? 'selected' : '' ?>>
                                            <?= esc(ucwords(str_replace('_', ' ', $s))) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                                <label>Reply to customer<textarea name="response" maxlength="5000" rows="3"></textarea></label>
                                <button class="small-button">Update</button>
                            </form>
                        <?php endif ?>
                    </article>
                <?php endforeach ?>
                <?php if (! $tickets): ?>
                    <p>No support tickets.</p>
                <?php endif ?>
            </section>

            <section class="portal-card">
                <h2>Technician reports</h2>
                <?php foreach ($reports as $r): ?>
                    <article class="list-row">
                        <strong>Job #<?= (int) $r['booking_id'] ?> · <?= esc($r['report_type']) ?></strong>
                        <p><?= esc($r['details']) ?></p>
                        <small><?= esc($r['first_name'] . ' ' . $r['last_name'] . ' · ' . $r['created_at']) ?></small>
                    </article>
                <?php endforeach ?>
                <?php if (! $reports): ?>
                    <p>No submitted reports.</p>
                <?php endif ?>
            </section>
        </div>

        <?php if ($role !== 'admin'): ?>
            <section class="portal-card">
                <h2>Announcements</h2>
                <?php foreach ($announcements as $announcement): ?>
                    <article class="list-row">
                        <strong><?= esc($announcement['title']) ?></strong>
                        <p><?= esc($announcement['message']) ?></p>
                    </article>
                <?php endforeach ?>
                <?php if (! $announcements): ?>
                    <p>No announcements.</p>
                <?php endif ?>
            </section>
        <?php endif ?>

        <?php if ($role === 'admin'): ?>
            <div class="portal-grid">
                <section class="portal-card">
                    <h2>Reports and service usage</h2>
                    <p><a class="small-button" href="<?= site_url('staff/report.csv') ?>">Download activity and revenue CSV</a></p>
                    <?php $maxUsage = max(1, ...array_map(fn ($x) => (int) $x['total'], $serviceUsage ?: [['total' => 1]])); ?>
                    <?php foreach ($serviceUsage as $usage): ?>
                        <div class="usage-row">
                            <span><?= esc($usage['service_name']) ?></span>
                            <div><i style="width:<?= (int) round(((int) $usage['total'] / $maxUsage) * 100) ?>%"></i></div>
                            <b><?= (int) $usage['total'] ?></b>
                        </div>
                    <?php endforeach ?>
                    <p>Revenue totals include payment entries marked paid.</p>
                </section>

                <section class="portal-card">
                    <h2>Recent technician check-ins</h2>
                    <?php foreach ($checkins as $checkin): ?>
                        <article class="list-row"><strong><?= esc($checkin['first_name'].' '.$checkin['last_name']) ?> · <?= esc($checkin['checked_in_at']) ?></strong><p><?= $checkin['booking_id'] ? 'Job #'.(int)$checkin['booking_id'] : 'General check-in' ?> · <?= esc($checkin['latitude']) ?>, <?= esc($checkin['longitude']) ?></p></article>
                    <?php endforeach ?>
                    <?php if (!$checkins): ?><p>No technician check-ins yet.</p><?php endif ?>
                </section>

                <section class="portal-card">
                    <h2>Recent system activity</h2>
                    <?php foreach ($recentActivity as $activity): ?><article class="list-row"><strong><?= esc($activity['first_name'] ? $activity['first_name'].' '.$activity['last_name'] : 'System') ?> · <?= esc(str_replace('_',' ',$activity['action'])) ?></strong><p><?= esc($activity['entity_type']) ?> <?= esc($activity['entity_id']??'') ?> · <?= esc($activity['created_at']) ?></p></article><?php endforeach ?>
                    <?php if (!$recentActivity): ?><p>No activity recorded yet.</p><?php endif ?>
                </section>

                <section class="portal-card">
                    <h2>Customer story review</h2>
                    <?php foreach ($testimonials as $story): ?>
                        <article class="list-row"><strong><?= esc($story['first_name'].' '.$story['last_name']) ?> · <?= esc(ucfirst($story['status'])) ?></strong><p><?= esc($story['quote']) ?></p>
                            <?php if ($story['status'] === 'pending'): ?><form method="post" action="<?= site_url('staff/testimonial/'.(int)$story['testimonial_id']) ?>"><?= csrf_field() ?><button class="small-button" name="status" value="approved">Approve</button><button class="small-button" name="status" value="rejected">Reject</button></form><?php endif ?>
                        </article>
                    <?php endforeach ?>
                    <?php if (!$testimonials): ?><p>No customer stories submitted.</p><?php endif ?>
                </section>

                <section class="portal-card">
                    <h2>Record a payment / receipt</h2>
                    <form method="post" action="<?= site_url('staff/payment') ?>">
                        <?= csrf_field() ?>
                        <label>Booking
                            <select name="booking_id" required>
                                <?php foreach ($bookings as $b): ?>
                                    <option value="<?= (int) $b['booking_id'] ?>">
                                        #<?= (int) $b['booking_id'] ?> · <?= esc($b['first_name'] . ' ' . $b['last_name'] . ' · ' . $b['service_name']) ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </label>
                        <label>Amount (₱)<input type="number" min="0" step="0.01" name="amount" required></label>
                        <label>Payment method<input name="payment_method" maxlength="50"></label>
                        <label>Receipt/reference number<input name="reference_number" maxlength="100"></label>
                        <button class="auth-submit">Record payment</button>
                    </form>
                </section>

                <section class="portal-card">
                    <h2>Services, price, and weekly availability</h2>
                    <form method="post" action="<?= site_url('staff/service') ?>">
                        <?= csrf_field() ?>
                        <label>Add service category<input name="service_name" maxlength="100" required></label>
                        <label>Description<textarea name="description"></textarea></label>
                        <button class="small-button">Add service</button>
                    </form>

                    <?php $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; ?>
                    <?php foreach ($services as $service): ?>
                        <form class="list-row" method="post" action="<?= site_url('staff/service/' . (int) $service['service_id']) ?>">
                            <?= csrf_field() ?>
                            <strong><?= esc($service['service_name']) ?></strong>
                            <label>Base price (₱)
                                <input type="number" name="base_price" min="0" step="0.01" value="<?= esc($service['base_price'] ?? '') ?>">
                            </label>
                            <label>
                                <input type="checkbox" name="is_active" value="1" <?= $service['is_active'] ? 'checked' : '' ?>>
                                Service active
                            </label>
                            <?php foreach ($dayNames as $day => $name): ?>
                                <?php $slot = $availabilityByService[(int) $service['service_id']][$day] ?? null; ?>
                                <div class="availability-row">
                                    <label>
                                        <input type="checkbox" name="available_days[]" value="<?= $day ?>" <?= $slot ? 'checked' : '' ?>>
                                        <?= $name ?>
                                    </label>
                                    <input aria-label="<?= $name ?> start" type="time" name="start_time_<?= $day ?>" value="<?= esc($slot ? substr($slot['starts_at'], 0, 5) : '09:00') ?>">
                                    <input aria-label="<?= $name ?> end" type="time" name="end_time_<?= $day ?>" value="<?= esc($slot ? substr($slot['ends_at'], 0, 5) : '17:00') ?>">
                                </div>
                            <?php endforeach ?>
                            <button class="small-button">Save service settings</button>
                        </form>
                    <?php endforeach ?>
                </section>

                <section class="portal-card">
                    <h2>Product catalog</h2>
                    <p>Public product listings only; inventory and online ordering are not enabled.</p>
                    <form method="post" action="<?= site_url('staff/product') ?>">
                        <?= csrf_field() ?>
                        <label>Product name<input name="product_name" maxlength="120" required></label>
                        <label>Description<textarea name="description" maxlength="5000"></textarea></label>
                        <label>Image URL<input type="url" name="image_url" maxlength="500" placeholder="https://..."></label>
                        <label>Display price (₱)<input type="number" name="price" min="0" step="0.01"></label>
                        <button class="small-button">Add product</button>
                    </form>
                    <?php foreach($products as $product): ?>
                        <form class="list-row" method="post" action="<?= site_url('staff/product/'.(int)$product['product_id']) ?>">
                            <?= csrf_field() ?>
                            <label>Name<input name="product_name" value="<?= esc($product['product_name']) ?>" maxlength="120" required></label>
                            <label>Description<textarea name="description" maxlength="5000"><?= esc($product['description']) ?></textarea></label>
                            <label>Image URL<input type="url" name="image_url" value="<?= esc($product['image_url']??'') ?>" maxlength="500"></label>
                            <label>Display price (₱)<input type="number" name="price" min="0" step="0.01" value="<?= esc($product['price']??'') ?>"></label>
                            <label><input type="checkbox" name="is_active" value="1" <?= $product['is_active']?'checked':'' ?>> Show on public website</label>
                            <button class="small-button">Save product</button>
                        </form>
                    <?php endforeach ?>
                </section>

                <section class="portal-card">
                    <h2>Customer notifications and employee announcements</h2>
                    <form method="post" action="<?= site_url('staff/announcement') ?>">
                        <?= csrf_field() ?>
                        <label>Title<input name="title" maxlength="160" required></label>
                        <label>Audience
                            <select name="audience">
                                <option value="customers">Customers</option>
                                <option value="employees">Employees</option>
                                <option value="all">Everyone</option>
                            </select>
                        </label>
                        <label>Type
                            <select name="announcement_type"><option value="promotion">Company news</option><option value="tip">Solar tip</option><option value="update">Service update</option><option value="system">System notice</option></select>
                        </label>
                        <label>Message<textarea name="message" required></textarea></label>
                        <button class="auth-submit">Publish announcement</button>
                    </form>
                </section>

                <section class="portal-card">
                    <h2>Company contact, social links, and privacy notice</h2>
                    <form method="post" action="<?= site_url('staff/settings') ?>">
                        <?= csrf_field() ?>
                        <label>Company phone<input name="company_phone" maxlength="30" value="<?= esc($siteSettings['company_phone'] ?? '09291230983') ?>" required></label>
                        <label>Company email<input type="email" name="company_email" maxlength="255" value="<?= esc($siteSettings['company_email'] ?? 'katherine.sinagaraw@sunsonsolar.com') ?>" required></label>
                        <label>Facebook URL<input type="url" name="facebook_url" value="<?= esc($siteSettings['facebook_url'] ?? '') ?>" placeholder="https://..."></label>
                        <label>Instagram URL<input type="url" name="instagram_url" value="<?= esc($siteSettings['instagram_url'] ?? '') ?>" placeholder="https://..."></label>
                        <label>LinkedIn URL<input type="url" name="linkedin_url" value="<?= esc($siteSettings['linkedin_url'] ?? '') ?>" placeholder="https://..."></label>
                        <label>Privacy notice<textarea name="privacy_notice" maxlength="5000" rows="4"><?= esc($siteSettings['privacy_notice'] ?? 'Account and service details are used to manage appointments, provide support, and maintain customer records. Customer stories are shown publicly only after the customer opts in and an administrator approves them.') ?></textarea></label>
                        <button class="small-button">Save company settings</button>
                    </form>
                </section>
            </div>
        <?php endif ?>
    </main>
</body>
</html>
