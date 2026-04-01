<?php
// api/config.php — единственная точка конфигурации SafePeak

define('DATA_PATH',        __DIR__ . '/../data/');
define('USERS_FILE',       DATA_PATH . 'users.json');
define('POLICIES_FILE',    DATA_PATH . 'policies.json');

define('INSURANCE_TYPES', [
    // Здоровье
    'health_basic' => [
        'category'    => 'health',
        'category_name' => 'Здоровье',
        'name'        => 'Базовая медицинская',
        'description' => 'Покрытие амбулаторного лечения, экстренной помощи и базовых анализов.',
        'icon'        => 'health',
        'price'       => 3500,
        'period'      => 'мес.',
    ],
    'health_premium' => [
        'category'    => 'health',
        'category_name' => 'Здоровье',
        'name'        => 'Премиум медицинская',
        'description' => 'Полный охват: стационар, операции, стоматология и зарубежное лечение.',
        'icon'        => 'health',
        'price'       => 8900,
        'period'      => 'мес.',
    ],
    'health_critical' => [
        'category'    => 'health',
        'category_name' => 'Здоровье',
        'name'        => 'Критические заболевания',
        'description' => 'Единовременная выплата при диагностировании онкологии, инфаркта или инсульта.',
        'icon'        => 'health',
        'price'       => 2100,
        'period'      => 'мес.',
    ],
    // Имущество
    'property_home' => [
        'category'    => 'property',
        'category_name' => 'Имущество',
        'name'        => 'Страхование жилья',
        'description' => 'Защита от пожара, затопления, кражи и стихийных бедствий.',
        'icon'        => 'property',
        'price'       => 1800,
        'period'      => 'мес.',
    ],
    'property_contents' => [
        'category'    => 'property',
        'category_name' => 'Имущество',
        'name'        => 'Страхование имущества',
        'description' => 'Защита ценного имущества: техники, украшений, мебели и предметов интерьера.',
        'icon'        => 'property',
        'price'       => 1200,
        'period'      => 'мес.',
    ],
    'property_business' => [
        'category'    => 'property',
        'category_name' => 'Имущество',
        'name'        => 'Коммерческая недвижимость',
        'description' => 'Комплексная защита офисов, складов и производственных помещений.',
        'icon'        => 'property',
        'price'       => 5600,
        'period'      => 'мес.',
    ],
    // Авто
    'auto_kasko' => [
        'category'    => 'auto',
        'category_name' => 'Авто',
        'name'        => 'КАСКО',
        'description' => 'Полная защита автомобиля: ДТП, угон, стихийные бедствия, ущерб.',
        'icon'        => 'auto',
        'price'       => 4200,
        'period'      => 'мес.',
    ],
    'auto_osago' => [
        'category'    => 'auto',
        'category_name' => 'Авто',
        'name'        => 'ОСАГО+',
        'description' => 'Расширенное ОСАГО с увеличенными лимитами и юридической поддержкой.',
        'icon'        => 'auto',
        'price'       => 1100,
        'period'      => 'мес.',
    ],
    // Жизнь
    'life_term' => [
        'category'    => 'life',
        'category_name' => 'Жизнь',
        'name'        => 'Срочное страхование жизни',
        'description' => 'Выплата семье в случае смерти застрахованного на протяжении срока полиса.',
        'icon'        => 'life',
        'price'       => 1600,
        'period'      => 'мес.',
    ],
    'life_saving' => [
        'category'    => 'life',
        'category_name' => 'Жизнь',
        'name'        => 'Накопительное страхование',
        'description' => 'Защита жизни и формирование накоплений с гарантированной доходностью.',
        'icon'        => 'life',
        'price'       => 6500,
        'period'      => 'мес.',
    ],
    // Путешествия
    'travel_basic' => [
        'category'    => 'travel',
        'category_name' => 'Путешествия',
        'name'        => 'Туристическая страховка',
        'description' => 'Медицинская помощь, отмена поездки и потеря багажа за рубежом.',
        'icon'        => 'travel',
        'price'       => 850,
        'period'      => 'поездка',
    ],
    'travel_annual' => [
        'category'    => 'travel',
        'category_name' => 'Путешествия',
        'name'        => 'Годовой мультивизовый полис',
        'description' => 'Неограниченное количество поездок в год с полным покрытием.',
        'icon'        => 'travel',
        'price'       => 4900,
        'period'      => 'год',
    ],
]);

// --- Сессия ---------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime',  (string)(3600 * 24 * 7));
    ini_set('session.cookie_lifetime', (string)(3600 * 24 * 7));
    session_start();
}

// --- Заголовки ответа -----------------------------------------------
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// --- Инициализация файлов данных ------------------------------------
if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0755, true);
}
foreach ([USERS_FILE, POLICIES_FILE] as $f) {
    if (!file_exists($f)) {
        file_put_contents($f, '[]', LOCK_EX);
    }
}

// --- Вспомогательная функция ответа --------------------------------
function respond(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// --- Читаем JSON-тело запроса --------------------------------------
function getInput(): array {
    static $parsed = null;
    if ($parsed === null) {
        $raw    = file_get_contents('php://input');
        $parsed = json_decode($raw ?: '{}', true) ?? [];
    }
    return $parsed;
}
