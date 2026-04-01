<?php
// api/UserModel.php

class UserModel {

    private static function all(): array {
        return json_decode(file_get_contents(USERS_FILE), true) ?? [];
    }

    private static function save(array $rows): void {
        file_put_contents(USERS_FILE,
            json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX);
    }

    public static function findByEmail(string $email): ?array {
        foreach (self::all() as $u) {
            if (strcasecmp($u['email'], $email) === 0) return $u;
        }
        return null;
    }

    public static function findById(string $id): ?array {
        foreach (self::all() as $u) {
            if ($u['id'] === $id) return $u;
        }
        return null;
    }

    public static function create(string $name, string $email, string $password, string $phone): array {
        $rows = self::all();
        $user = [
            'id'         => uniqid('u_', true),
            'name'       => $name,
            'email'      => $email,
            'phone'      => $phone,
            'password'   => password_hash($password, PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $rows[] = $user;
        self::save($rows);
        return $user;
    }

    public static function safe(array $u): array {
        unset($u['password']);
        return $u;
    }

    public static function verifyPassword(array $u, string $pw): bool {
        return password_verify($pw, $u['password']);
    }
}
