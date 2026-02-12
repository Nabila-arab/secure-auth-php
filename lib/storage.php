<?php
declare(strict_types=1);

function users_file(): string {
    return __DIR__ . '/../data/users.json';
}

function load_users(): array {
    $file = users_file();
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    }
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function save_users(array $users): void {
    file_put_contents(users_file(), json_encode($users, JSON_PRETTY_PRINT));
}

function find_user(array $users, string $username): ?array {
    return $users[$username] ?? null;
}