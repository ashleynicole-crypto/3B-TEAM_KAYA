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
            <article>
                <strong>₱<?= number_format((float) $counts['revenue'], 2) ?></strong>
                <span>Recorded revenue</span>
            </article>
        </div>

        <?php if (in_array($role, ['admin', 'hr'], true)): ?>
            <nav class="staff-links">
                <a href="<?= site_url('staff/users') ?>">Manage employee accounts</a>
            </nav>
        <?php endif ?>

        <section class="portal-card">
            <h2>Schedule overview and dispatch</h2>
            <div class="table-wrap">
                <table>
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
                                                value="<?= esc(date('Y-m-d\TH:i', strtotime($b['scheduled_at']))) ?>"
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
                        <?php if (in_array($role, ['admin', 'dispatcher'], true)): ?>
                            <form method="post" action="<?= site_url('staff/ticket/' . (int) $t['ticket_id']) ?>">
                                <?= csrf_field() ?>
                                <select name="status">
                                    <?php foreach (['open', 'in_progress', 'resolved', 'closed'] as $s): ?>
                                        <option value="<?= $s ?>" <?= $s === $t['status'] ? 'selected' : '' ?>>
                                            <?= esc(ucwords(str_replace('_', ' ', $s))) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
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
                        <label>Message<textarea name="message" required></textarea></label>
                        <button class="auth-submit">Publish announcement</button>
                    </form>
                </section>
            </div>
        <?php endif ?>
    </main>
</body>
</html>
