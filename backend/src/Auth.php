<?php
namespace CabalOnline;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class Auth {
    private $db;
    private $jwt_secret;
    private $jwt_expire;

    public function __construct(\PDO $db) {
        $this->db = $db;
        $this->loadConfig();
    }

    private function loadConfig() {
        $envFile = __DIR__ . '/../../config/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                list($key, $value) = explode('=', $line, 2);
                $this->config[trim($key)] = trim($value);
            }
        } else {
            $this->config = [
                'JWT_SECRET' => 'your_jwt_secret_key_here_make_it_long_and_random',
                'JWT_EXPIRE' => '3600'
            ];
        }
        $this->jwt_secret = $this->config['JWT_SECRET'];
        $this->jwt_expire = (int)$this->config['JWT_EXPIRE'];
    }

    public function register($username, $password, $email, $full_name, $personal_code) {
        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ? OR personal_code = ?");
            $stmt->execute([$username, $email, $personal_code]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Username, email or personal code already exists");
            }

            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, password_hash, email, full_name, personal_code) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$username, $password_hash, $email, $full_name, $personal_code]);

            $user_id = $this->db->lastInsertId();

            // Create game profile
            $stmt = $this->db->prepare(
                "INSERT INTO game_profiles (user_id) VALUES (?)"
            );
            $stmt->execute([$user_id]);

            return [
                'success' => true,
                'user_id' => $user_id,
                'message' => 'Registration successful'
            ];
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function login($username, $password) {
        try {
            // Get user by username
            $stmt = $this->db->prepare(
                "SELECT id, username, password_hash, role, is_active FROM users WHERE username = ?"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception("Invalid credentials");
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                throw new Exception("Invalid credentials");
            }

            if (!$user['is_active']) {
                throw new Exception("Account is deactivated");
            }

            // Update last login
            $stmt = $this->db->prepare(
                "UPDATE users SET last_login = NOW() WHERE id = ?"
            );
            $stmt->execute([$user['id']]);

            // Generate JWT token
            $payload = [
                'iss' => 'cabal_online',
                'aud' => 'cabal_online',
                'iat' => time(),
                'nbf' => time(),
                'exp' => time() + $this->jwt_expire,
                'data' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ];

            $jwt = JWT::encode($payload, $this->jwt_secret, 'HS256');

            // Store token in session table (for blacklisting if needed)
            $token_hash = hash('sha256', $jwt);
            $stmt = $this->db->prepare(
                "INSERT INTO user_sessions (user_id, token_hash, expires_at) 
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))"
            );
            $stmt->execute([$user['id'], $token_hash, $this->jwt_expire]);

            return [
                'success' => true,
                'token' => $jwt,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ],
                'message' => 'Login successful'
            ];
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->jwt_secret, 'HS256'));
            $arr = (array) $decoded;

            // Check if token is blacklisted
            $token_hash = hash('sha256', $token);
            $stmt = $this->db->prepare(
                "SELECT id FROM user_sessions WHERE token_hash = ? AND is_revoked = 0 AND expires_at > NOW()"
            );
            $stmt->execute([$token_hash]);
            if ($stmt->rowCount() === 0) {
                throw new Exception("Token is invalid or expired");
            }

            return $arr['data'];
        } catch (Exception $e) {
            error_log("Token validation error: " . $e->getMessage());
            return false;
        }
    }

    public function logout($token) {
        try {
            $token_hash = hash('sha256', $token);
            $stmt = $this->db->prepare(
                "UPDATE user_sessions SET is_revoked = 1 WHERE token_hash = ?"
            );
            $stmt->execute([$token_hash]);
            return [
                'success' => true,
                'message' => 'Logged out successfully'
            ];
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function verifyRecovery($email, $personal_code) {
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM users WHERE email = ? AND personal_code = ? AND is_active = 1"
            );
            $stmt->execute([$email, $personal_code]);
            $user = $stmt->fetch();
            if ($user) {
                return $user['id'];
            }
            return false;
        } catch (Exception $e) {
            error_log("Recovery verification error: " . $e->getMessage());
            return false;
        }
    }

    public function resetPassword($user_id, $new_password) {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare(
                "UPDATE users SET password_hash = ? WHERE id = ?"
            );
            $stmt->execute([$password_hash, $user_id]);
            // Invalidate any existing sessions (optional)
            $stmt = $this->db->prepare(
                "UPDATE user_sessions SET is_revoked = 1 WHERE user_id = ?"
            );
            $stmt->execute([$user_id]);
            return [
                'success' => true,
                'message' => 'Password reset successful'
            ];
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getDashboardData($user_id) {
        try {
            // Get user info
            $stmt = $this->db->prepare(
                "SELECT id, username, email, full_name, created_at, last_login, role FROM users WHERE id = ?"
            );
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception("User not found");
            }

            // Get game profile
            $stmt = $this->db->prepare(
                "SELECT character_name, level, class, experience, gold, last_played FROM game_profiles WHERE user_id = ?"
            );
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();

            // Get rankings (example: top 10 by level)
            $stmt = $this->db->prepare(
                "SELECT u.username, gp.level, gp.class 
                 FROM users u 
                 JOIN game_profiles gp ON u.id = gp.user_id 
                 ORDER BY gp.level DESC, u.username ASC 
                 LIMIT 10"
            );
            $stmt->execute();
            $rankings = $stmt->fetchAll();

            return [
                'success' => true,
                'user' => $user,
                'profile' => $profile ?: [
                    'character_name' => null,
                    'level' => 1,
                    'class' => null,
                    'experience' => 0,
                    'gold' => 0,
                    'last_played' => null
                ],
                'rankings' => $rankings
            ];
        } catch (Exception $e) {
            error_log("Dashboard data error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}