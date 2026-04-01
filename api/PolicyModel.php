<?php
// api/PolicyModel.php

class PolicyModel {

    private static function all(): array {
        return json_decode(file_get_contents(POLICIES_FILE), true) ?? [];
    }

    private static function save(array $rows): void {
        file_put_contents(POLICIES_FILE,
            json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX);
    }

    public static function byUser(string $userId): array {
        return array_values(array_filter(self::all(), fn($p) => $p['user_id'] === $userId));
    }

    public static function create(string $userId, string $type): array {
        $types = INSURANCE_TYPES;
        if (!isset($types[$type])) {
            throw new InvalidArgumentException('Неверный тип страхования');
        }
        $t = $types[$type];
        $policy = [
            'id'            => uniqid('p_', true),
            'user_id'       => $userId,
            'type'          => $type,
            'name'          => $t['name'],
            'category'      => $t['category'],
            'category_name' => $t['category_name'],
            'price'         => $t['price'],
            'period'        => $t['period'],
            'status'        => 'pending',
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        $rows   = self::all();
        $rows[] = $policy;
        self::save($rows);
        return $policy;
    }
}
