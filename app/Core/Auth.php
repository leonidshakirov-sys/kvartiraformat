<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Auth
{
    public function __construct(private Database $db)
    {
    }

    public function user(): ?array
    {
        if (empty($_SESSION['admin_user_id'])) {
            return null;
        }
        $pdo = $this->db->pdo();
        if (!$pdo) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT id, username, role, created_at FROM admin_users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['admin_user_id']]);
        return $stmt->fetch() ?: null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function attempt(string $username, string $password, string $ip, int $limit, int $windowMinutes): bool
    {
        $pdo = $this->db->pdo();
        if (!$pdo) {
            return false;
        }
        $this->pruneAttempts($pdo, $windowMinutes);
        $count = $this->failedAttempts($pdo, $username, $ip, $windowMinutes);
        if ($count >= $limit) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        $ok = $user && password_verify($password, $user['password_hash']);

        $pdo->prepare('INSERT INTO login_attempts (username, ip_address, success, created_at) VALUES (?, ?, ?, NOW())')
            ->execute([$username, $ip, $ok ? 1 : 0]);

        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['admin_user_id'] = (int)$user['id'];
            $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([(int)$user['id']]);
            return true;
        }
        return false;
    }

    public function logout(): void
    {
        unset($_SESSION['admin_user_id']);
        session_regenerate_id(true);
    }

    public function hasUsers(): bool
    {
        $pdo = $this->db->pdo();
        if (!$pdo) {
            return false;
        }
        try {
            return (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function createFirstAdmin(string $username, string $password): bool
    {
        $pdo = $this->db->pdo();
        if (!$pdo || $this->hasUsers()) {
            return false;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, role, is_active, created_at) VALUES (?, ?, "owner", 1, NOW())');
        return $stmt->execute([$username, $hash]);
    }

    private function pruneAttempts(PDO $pdo, int $windowMinutes): void
    {
        $pdo->prepare('DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)')->execute([$windowMinutes * 3]);
    }

    private function failedAttempts(PDO $pdo, string $username, string $ip, int $windowMinutes): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND (username = ? OR ip_address = ?) AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $stmt->execute([$username, $ip, $windowMinutes]);
        return (int)$stmt->fetchColumn();
    }
}
