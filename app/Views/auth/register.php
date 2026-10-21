<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create account | Sun Son Solar</title>
    <link rel="stylesheet" href="<?= base_url('style1.css') ?>">
</head>
<body class="auth-split-body signup-body">
    <main class="auth-split-page signup-page">
        <div class="auth-photo signup-photo">
            <img class="side-logo signup-side-logo" src="<?= base_url('PCTURES/sunsun%20logo.png') ?>" alt="Sun Son Solar">
        </div>

        <section class="auth-pane signup-pane">
            <p class="auth-welcome">Welcome!</p>
            <div class="auth-card signup-card">
                <h1>Create customer account</h1>

                <?php if (! empty($errors)): ?>
                    <p role="alert"><?= esc(implode(' ', $errors)) ?></p>
                <?php endif ?>

                <form class="auth-form" method="post" action="<?= site_url('register') ?>">
                    <?= csrf_field() ?>
                    <div class="signup-grid">
                        <?php
                        $fields = [
                            'first_name'  => 'First name',
                            'middle_name' => 'Middle name',
                            'last_name'   => 'Last name',
                            'birthdate'   => 'Birthdate',
                            'gender'      => 'Gender',
                            'email'       => 'Email address',
                            'phone_number'=> 'Contact number',
                            'address'     => 'Address',
                            'username'    => 'Username',
                            'password'    => 'Password',
                        ];
                        ?>

                        <?php foreach ($fields as $name => $label): ?>
                            <div class="auth-field">
                                <label for="<?= esc($name) ?>"><?= esc($label) ?></label>

                                <?php if ($name === 'gender'): ?>
                                    <select id="gender" name="gender" required>
                                        <option value="">Choose</option>
                                        <option>Female</option>
                                        <option>Male</option>
                                        <option>Other</option>
                                    </select>
                                <?php elseif ($name === 'address'): ?>
                                    <textarea id="address" name="address" required></textarea>
                                <?php else: ?>
                                    <?php
                                    $type = match ($name) {
                                        'birthdate' => 'date',
                                        'email'     => 'email',
                                        'password'  => 'password',
                                        default     => 'text',
                                    };
                                    $autocomplete = match ($name) {
                                        'password' => 'new-password',
                                        'email'    => 'email',
                                        default    => 'off',
                                    };
                                    ?>
                                    <input
                                        id="<?= esc($name) ?>"
                                        name="<?= esc($name) ?>"
                                        type="<?= $type ?>"
                                        autocomplete="<?= $autocomplete ?>"
                                        <?= $name === 'middle_name' ? '' : 'required' ?>
                                    >
                                <?php endif ?>
                            </div>
                        <?php endforeach ?>
                    </div>

                    <button class="auth-submit">Create account</button>
                </form>

                <p class="auth-switch">Already registered? <a href="<?= site_url('login') ?>">Log in</a></p>
            </div>
        </section>
    </main>
</body>
</html>
