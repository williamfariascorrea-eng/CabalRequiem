<?php
namespace CabalOnline;

use PDO;
use PDOException;
use Exception;

class Database {
    private static $instance = null;
    private $pdo;
    private $config;

    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }

    private function loadConfig() {
        $envFile = __DIR__ . '/../../config/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                if (strpos($line, '=') === false) {
                    continue;
                }
                list($key, $value) = explode('=', $line, 2);
                $this->config[trim($key)] = trim($value);
            }
        } else {
            $this->config = [
                'DB_TYPE' => 'sqlsrv',
                'DB_HOST' => 'localhost',
                'DB_PORT' => '1433',
                'DB_NAME' => 'CabalOnline',
                'DB_USER' => 'sa',
                'DB_PASS' => ''
            ];
        }
    }

    private function connect() {
        try {
            $dbType = $this->config['DB_TYPE'] ?? 'sqlsrv';
            
            if ($dbType === 'sqlsrv') {
                $dsn = sprintf(
                    'sqlsrv:Server=%s,%s;Database=%s',
                    $this->config['DB_HOST'],
                    $this->config['DB_PORT'],
                    $this->config['DB_NAME']
                );
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $this->config['DB_HOST'],
                    $this->config['DB_PORT'],
                    $this->config['DB_NAME']
                );
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->pdo = new PDO(
                $dsn,
                $this->config['DB_USER'],
                $this->config['DB_PASS'],
                $options
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
}