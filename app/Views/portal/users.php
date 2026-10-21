<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Account management | Sun Son Solar</title><link rel="stylesheet" href="<?= base_url('style1.css') ?>">
</head>
<body class="portal-body">
<header class="portal-header"><a href="<?= site_url('staff') ?>">← Operations</a><strong>Account management</strong><a href="<?= site_url('logout') ?>">Log out</a></header>
<main class="portal-main">
    <h1>Employee registration and user management</h1>
    <?php if ($msg=session()->getFlashdata('message')): ?><p class="notice"><?= esc($msg) ?></p><?php endif ?>
    <?php if ($errs=session()->getFlashdata('form_errors')): ?><p class="notice notice--error"><?= esc(implode(' ', $errs)) ?></p><?php endif ?>
    <?php if ($reset=session()->getFlashdata('reset_credentials')): ?>
        <section class="portal-card"><h2>Temporary password — share securely once</h2><p>Account: <?= esc($reset['name']) ?> (<?= esc($reset['username']) ?>). They must change this password at their next sign-in.</p><p><strong><?= esc($reset['password']) ?></strong></p></section>
    <?php endif ?>

    <section class="portal-card">
        <h2>Employee announcements</h2>
        <?php foreach ($announcements as $announcement): ?><article class="list-row"><strong><?= esc($announcement['title']) ?></strong><p><?= esc($announcement['message']) ?></p><small><?= esc($announcement['publish_at']) ?></small></article><?php endforeach ?>
        <?php if (!$announcements): ?><p>No announcements yet.</p><?php endif ?>
        <h3>Send an employee announcement</h3>
        <form method="post" action="<?= site_url('staff/announcement') ?>"><?= csrf_field() ?><label>Title<input name="title" maxlength="160" required></label><input type="hidden" name="audience" value="employees"><label>Type<select name="announcement_type"><option value="update">Service update</option><option value="system">System notice</option></select></label><label>Message<textarea name="message" required></textarea></label><button class="small-button">Publish to employees</button></form>
    </section>

    <section class="portal-card">
        <h2>Search accounts</h2>
        <form method="get" action="<?= site_url('staff/users') ?>" class="signup-grid">
            <label>Name, email, or username<input name="q" value="<?= esc($search) ?>"></label>
            <label>Employee role<select name="role"><option value="">All roles</option><?php foreach (['admin'=>'Administrator','hr'=>'HR','dispatcher'=>'Dispatcher','technician'=>'Technician','employee'=>'Employee'] as $value=>$label): ?><option value="<?= esc($value) ?>" <?= $roleFilter===$value?'selected':'' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
            <label>Account status<select name="status"><option value="">All statuses</option><option value="active" <?= $statusFilter==='active'?'selected':'' ?>>Active</option><option value="inactive" <?= $statusFilter==='inactive'?'selected':'' ?>>Inactive</option></select></label>
            <button class="small-button">Search</button>
        </form>
    </section>

    <div class="portal-grid">
        <section class="portal-card">
            <h2>Create employee account</h2>
            <p>Employees cannot self-register. Set a temporary password and share it securely. They must change it at first sign-in.</p>
            <form method="post" action="<?= site_url('staff/users/create') ?>">
                <?= csrf_field() ?><div class="signup-grid">
                <?php foreach (['first_name'=>'First name','middle_name'=>'Middle name','last_name'=>'Last name','birthdate'=>'Birthdate','gender'=>'Gender','email'=>'Email','phone_number'=>'Phone','address'=>'Address','username'=>'Username','password'=>'Temporary password (12+ characters)'] as $key=>$label): ?>
                    <label><?= esc($label) ?><?php if ($key==='gender'): ?><select name="gender"><option>Female</option><option>Male</option><option>Other</option></select><?php elseif ($key==='address'): ?><textarea name="address" required></textarea><?php else: ?><input name="<?= esc($key) ?>" <?= $key==='birthdate'?'type="date"':($key==='email'?'type="email"':($key==='password'?'type="password"':'text')) ?> <?= $key==='middle_name'?'':'required' ?>><?php endif ?></label>
                <?php endforeach ?>
                <label>Department<select name="department_id" required><?php foreach ($departments as $d): ?><option value="<?= (int)$d['department_id'] ?>"><?= esc($d['department_name']) ?></option><?php endforeach ?></select></label>
                <label>Role<select name="role"><option value="employee">Employee</option><option value="technician">Technician</option><option value="dispatcher">Dispatcher</option><?php if (session('role')==='admin'): ?><option value="hr">HR</option><option value="admin">Administrator</option><?php endif ?></select></label>
                </div><button class="auth-submit">Create employee</button>
            </form>
        </section>

        <section class="portal-card portal-card--wide">
            <h2>Employees (<?= count($employees) ?>)</h2>
            <?php foreach ($employees as $employee): ?>
                <article class="list-row">
                    <strong><?= esc($employee['first_name'].' '.$employee['last_name']) ?> · <?= esc(ucfirst($employee['role'])) ?> · <?= $employee['is_active']?'Active':'Inactive' ?></strong>
                    <p><?= esc($employee['department_name'].' · '.$employee['username'].' · '.$employee['email']) ?> · Created <?= esc($employee['created_at']) ?></p>
                    <details><summary>Edit employee</summary>
                        <form method="post" action="<?= site_url('staff/users/'.(int)$employee['employee_id']) ?>">
                            <?= csrf_field() ?>
                            <?php foreach (['first_name'=>'First name','middle_name'=>'Middle name','last_name'=>'Last name','birthdate'=>'Birthdate','gender'=>'Gender','email'=>'Email','phone_number'=>'Phone','address'=>'Address','username'=>'Username'] as $key=>$label): ?>
                                <label><?= esc($label) ?><?php if ($key==='gender'): ?><select name="gender"><?php foreach(['Female','Male','Other'] as $gender): ?><option <?= $employee['gender']===$gender?'selected':'' ?>><?= $gender ?></option><?php endforeach ?></select><?php elseif ($key==='address'): ?><textarea name="address" required><?= esc($employee['address']) ?></textarea><?php else: ?><input name="<?= esc($key) ?>" value="<?= esc($employee[$key]??'') ?>" <?= $key==='birthdate'?'type="date"':($key==='email'?'type="email"':'') ?> <?= $key==='middle_name'?'':'required' ?>><?php endif ?></label>
                            <?php endforeach ?>
                            <label>Department<select name="department_id"><?php foreach ($departments as $d): ?><option value="<?= (int)$d['department_id'] ?>" <?= (int)$d['department_id']===(int)$employee['department_id']?'selected':'' ?>><?= esc($d['department_name']) ?></option><?php endforeach ?></select></label>
                            <label>Role<select name="role"><?php foreach (['employee','technician','dispatcher','hr','admin'] as $r): ?><option value="<?= esc($r) ?>" <?= $employee['role']===$r?'selected':'' ?>><?= esc(ucfirst($r)) ?></option><?php endforeach ?></select></label>
                            <button class="small-button">Save employee details</button>
                        </form>
                    </details>
                    <?php if ((int)$employee['employee_id']!==(int)session('user_id')): ?>
                        <form method="post" action="<?= site_url('staff/users/'.(int)$employee['employee_id'].'/status') ?>"><?= csrf_field() ?><input type="hidden" name="is_active" value="<?= $employee['is_active']?'0':'1' ?>"><button class="small-button"><?= $employee['is_active']?'Deactivate':'Activate' ?> account</button></form>
                        <form method="post" action="<?= site_url('staff/users/'.(int)$employee['employee_id'].'/reset-password') ?>" onsubmit="return confirm('Create a new temporary password for this employee?')"><?= csrf_field() ?><button class="small-button">Reset password</button></form>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
            <?php if (!$employees): ?><p>No employees match this search.</p><?php endif ?>
        </section>
    </div>

    <section class="portal-card">
        <h2>Customers (<?= count($customers) ?>)</h2>
        <?php foreach ($customers as $customer): ?>
            <article class="list-row"><strong><?= esc($customer['first_name'].' '.$customer['last_name']) ?> · <?= $customer['is_active']?'Active':'Inactive' ?></strong><p><?= esc($customer['username'].' · '.$customer['email'].' · '.$customer['phone_number']) ?> · Created <?= esc($customer['created_at']) ?></p>
                <?php if (session('role')==='admin'): ?>
                    <form method="post" action="<?= site_url('staff/customers/'.(int)$customer['customer_id'].'/status') ?>"><?= csrf_field() ?><input type="hidden" name="is_active" value="<?= $customer['is_active']?'0':'1' ?>"><button class="small-button"><?= $customer['is_active']?'Deactivate':'Activate' ?> account</button></form>
                    <form method="post" action="<?= site_url('staff/customers/'.(int)$customer['customer_id'].'/reset-password') ?>" onsubmit="return confirm('Create a new temporary password for this customer?')"><?= csrf_field() ?><button class="small-button">Reset password</button></form>
                <?php endif ?>
            </article>
        <?php endforeach ?>
        <?php if (!$customers): ?><p>No customers match this search.</p><?php endif ?>
    </section>
</main>
</body>
</html>
