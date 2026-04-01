<?php
// api/policies.php — apply / my / types
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/UserModel.php';
require_once __DIR__ . '/PolicyModel.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'types':
        // Публичный список типов страхований
        respond(200, ['types' => INSURANCE_TYPES]);

    case 'apply':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['error' => 'Метод не разрешён']);
        if (empty($_SESSION['user_id']))           respond(401, ['error' => 'Необходима авторизация']);

        $in   = getInput();
        $type = htmlspecialchars(strip_tags(trim($in['type'] ?? '')), ENT_QUOTES, 'UTF-8');

        if (!isset(INSURANCE_TYPES[$type])) respond(400, ['error' => 'Неверный тип страхования']);

        $user = UserModel::findById($_SESSION['user_id']);
        if (!$user) respond(401, ['error' => 'Пользователь не найден. Войдите снова.']);

        try {
            $policy   = PolicyModel::create($_SESSION['user_id'], $type);
            $typeName = INSURANCE_TYPES[$type]['name'];
            $phone    = $user['phone'] ?: 'не указан';
            respond(200, [
                'success' => true,
                'policy'  => $policy,
                'message' => "Заявка на «{$typeName}» успешно отправлена! В ближайшее время с вами свяжется менеджер по телефону {$phone}, а на почту {$user['email']} будет выслано письмо с деталями полиса.",
            ]);
        } catch (Exception $e) {
            respond(400, ['error' => $e->getMessage()]);
        }

    case 'my':
        if (empty($_SESSION['user_id'])) respond(401, ['error' => 'Необходима авторизация']);
        respond(200, ['policies' => PolicyModel::byUser($_SESSION['user_id'])]);

    default:
        respond(404, ['error' => 'Неизвестный action']);
}
