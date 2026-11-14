<?php
declare(strict_types=1);

/** Login, logout and role checks. */
class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT user_id, username, full_name, password_hash, role
               FROM app_user WHERE username = :u AND is_active = TRUE'
        );
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch();

        // Always run password_verify so response time doesn't reveal valid usernames.
        $hash = $user['password_hash'] ?? '$2y$10$/4qWwd.IAq8Zg5e8dMkPGe5HCEUZoszU56XLjahhkJJDe99xhzK3G';
        if (!password_verify($password, $hash) || !$user) {
            return false;
        }

        session_regenerate_id(true);                 // prevents session fixation
        $_SESSION['user'] = [
            'id'        => (int) $user['user_id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
        ];
        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    /** Call at the top of every protected page. */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('login.php');
        }
    }

    /** Call on pages/actions that only administrators may use. */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            flash('danger', 'Access denied. This function is for administrators only.');
            redirect('index.php');
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];                  // forget the user and the CSRF token
        session_regenerate_id(true);     // brand-new session id for the next visitor/login
    }
}
