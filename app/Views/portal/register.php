<?php
declare(strict_types=1);

require __DIR__ . '/_common.php';

$data = requestData();
$firstName = textValue($data, 'firstName');
$middleName = textValue($data, 'middleName');
$lastName = textValue($data, 'lastName');
$birthDate = textValue($data, 'birthDate');
$gender = textValue($data, 'gender');
$address = textValue($data, 'address');
$email = textValue($data, 'email');
$phone = textValue($data, 'phone');
$username = textValue($data, 'newUsername');
$password = is_string($data['newPassword'] ?? null) ? $data['newPassword'] : '';
$confirmPassword = is_string($data['confirmPassword'] ?? null) ? $data['confirmPassword'] : '';

if ($firstName === '' || strlen($firstName) > 50 || $lastName === '' || strlen($lastName) > 50) {
    respond(['error' => 'Enter a first name and last name (up to 50 characters each).'], 422);
}
if (strlen($middleName) > 50 || strlen($address) > 255 || $address === '') {
    respond(['error' => 'Enter a valid address.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
    respond(['error' => 'Enter a valid email address.'], 422);
}
if (!preg_match('/^(?=.*\d)[+()0-9.\s-]{7,20}$/D', $phone)) {
    respond(['error' => 'Enter a phone number using 7 to 20 characters, including at least one digit.'], 422);
}
if (!preg_match('/^[A-Za-z0-9._-]{3,100}$/D', $username)) {
    respond(['error' => 'Username must be 3 to 100 characters using letters, numbers, periods, underscores, or hyphens.'], 422);
}
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    respond(['error' => 'Password must be at least 8 characters and include a letter and a number.'], 422);
}
if (!hash_equals($password, $confirmPassword)) {
    respond(['error' => 'Passwords do not match.'], 422);
}
$date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
if (!$date || $date->format('Y-m-d') !== $birthDate || $birthDate > date('Y-m-d')) {
    respond(['error' => 'Enter a valid date of birth that is not in the future.'], 422);
}
if (!in_array($gender, ['Female', 'Male', 'Other'], true)) {
    respond(['error' => 'Select a valid gender option.'], 422);
}

try {
    $pdo = database();
    $check = $pdo->prepare('SELECT username, email, phone_number FROM REGISTRATION WHERE username = :username OR email = :email OR phone_number = :phone_number LIMIT 1');
    $check->execute(['username' => $username, 'email' => $email, 'phone_number' => $phone]);
    if ($check->fetch()) {
        respond(['error' => 'That username, email, or phone number is already registered.'], 409);
    }

    $insert = $pdo->prepare(
        'INSERT INTO REGISTRATION (first_name, middle_name, last_name, birthdate, gender, email, phone_number, address, username, `password`)
         VALUES (:first_name, :middle_name, :last_name, :birthdate, :gender, :email, :phone_number, :address, :username, :password_hash)'
    );
    $insert->execute([
        'first_name' => $firstName,
        'middle_name' => $middleName !== '' ? $middleName : null,
        'last_name' => $lastName,
        'birthdate' => $birthDate,
        'gender' => $gender,
        'address' => $address,
        'email' => $email,
        'phone_number' => $phone,
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    respond(['ok' => true], 201);
} catch (PDOException $error) {
    if ($error->getCode() === '23000') {
        respond(['error' => 'That username, email, or phone number is already registered.'], 409);
    }
    databaseUnavailable($error);
}
