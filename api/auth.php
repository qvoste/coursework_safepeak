<?php
// api/auth.php  — register / login / logout / check
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/UserModel.php';

$action = $_GET['action'] ?? '';

// ── Валидация ────────────────────────────────────────────
function sanitize(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}
function validateEmail(string $e): bool {
    return filter_var($e, FILTER_VALIDATE_EMAIL) !== false;
}
function validatePassword(string $pw): ?string {
    if (strlen($pw) < 8)                         return 'Пароль минимум 8 символов';
    if (!preg_match('/[A-Za-zА-Яа-яЁё]/', $pw)) return 'Пароль должен содержать букву';
    if (!preg_match('/\d/', $pw))                return 'Пароль должен содержать цифру';
    return null;
}
function validateName(string $n): bool {
    return strlen($n) >= 2 && strlen($n) <= 100 && preg_match('/^[\p{L}\s\-]+$/u', $n);
}
function validatePhone(string $p): bool {
    if ($p === '') return true; // необязательно
    return preg_match('/^[\+\d\s\(\)\-]{7,20}$/', $p) === 1;
}

// ── Маршрутизация ────────────────────────────────────────
switch ($action) {

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'Метод не разрешён']);
        $in       = getInput();
        $name     = sanitize($in['name']     ?? '');
        $email    = sanitize($in['email']    ?? '');
        $password =          $in['password'] ?? '';
        $phone    = sanitize($in['phone']    ?? '');

        if (!validateName($name))           respond(400, ['error' => 'Имя: минимум 2 символа, только буквы']);
        if (!validateEmail($email))         respond(400, ['error' => 'Введите корректный email']);
        if (!validatePhone($phone))         respond(400, ['error' => 'Некорректный номер телефона']);
        if ($err = validatePassword($password)) respond(400, ['error' => $err]);

        if (UserModel::findByEmail($email)) respond(409, ['error' => 'Пользователь с таким email уже существует']);

        $user = UserModel::create($name, $email, $password, $phone);
        $_SESSION['user_id'] = $user['id'];
        respond(200, ['success' => true, 'user' => UserModel::safe($user)]);

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'Метод не разрешён']);
        $in       = getInput();
        $email    = sanitize($in['email']    ?? '');
        $password =          $in['password'] ?? '';

        if (!validateEmail($email) || !$password) respond(400, ['error' => 'Введите email и пароль']);

        $user = UserModel::findByEmail($email);
        if (!$user || !UserModel::verifyPassword($user, $password))
            respond(401, ['error' => 'Неверный email или пароль']);

        $_SESSION['user_id'] = $user['id'];
        respond(200, ['success' => true, 'user' => UserModel::safe($user)]);

    case 'logout':
        session_destroy();
        respond(200, ['success' => true]);

    case 'check':
        if (!empty($_SESSION['user_id'])) {
            $user = UserModel::findById($_SESSION['user_id']);
            if ($user) respond(200, ['authenticated' => true, 'user' => UserModel::safe($user)]);
        }
        respond(200, ['authenticated' => false]);

    default:
        respond(404, ['error' => 'Неизвестный action']);
}
