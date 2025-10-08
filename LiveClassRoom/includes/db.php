<?php
// Database config for wpC_liveclassroom_token_analytics
define('DB_HOST', 'localhost'); // Your MySQL host
define('DB_NAME', 'your_database_name'); // DB containing wpC_ tables
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('TABLE_PREFIX', 'wpC_');

class Database {
    private static $pdo;

    public static function connect() {
        if (!self::$pdo) {
            self::$pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }
        return self::$pdo;
    }

    public static function logToken($data) {
        $pdo = self::connect();
        $stmt = $pdo->prepare("
            INSERT INTO " . TABLE_PREFIX . "liveclassroom_token_analytics
            (token_hash, user_role, room_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            hash('sha256', $data['token']),
            $data['role'],
            $data['room_id'],
            $data['ip'],
            $data['user_agent'],
            date('Y-m-d H:i:s', $data['expires_at'])
        ]);
    }
}