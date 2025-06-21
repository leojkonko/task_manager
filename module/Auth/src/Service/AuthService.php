<?php

declare(strict_types=1);

namespace Auth\Service;

use Auth\Model\User;
use PDO;
use PDOException;

class AuthService
{
    private PDO $pdo;
    private int $maxFailedAttempts = 5;
    private int $lockoutDuration = 1800; // 30 minutos

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function authenticate(string $username, string $password, string $ipAddress, string $userAgent): ?User
    {
        try {
            // Buscar usuário por username ou email
            $stmt = $this->pdo->prepare(
                "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'"
            );
            $stmt->execute([$username, $username]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) {
                $this->logAuthAttempt(null, $username, 'login_failed', $ipAddress, $userAgent, 'User not found');
                return null;
            }

            $user = $this->createUserFromArray($userData);

            // Verificar se a conta está bloqueada
            if ($user->isLocked()) {
                $this->logAuthAttempt($user->getId(), $username, 'login_failed', $ipAddress, $userAgent, 'Account locked');
                return null;
            }

            // Verificar senha
            if (!$user->verifyPassword($password)) {
                $this->handleFailedLogin($user, $ipAddress, $userAgent);
                return null;
            }

            // Login bem-sucedido
            $this->handleSuccessfulLogin($user, $ipAddress, $userAgent);
            return $user;

        } catch (PDOException $e) {
            error_log("Authentication error: " . $e->getMessage());
            return null;
        }
    }

    public function register(string $username, string $email, string $password, string $fullName): ?User
    {
        try {
            // Verificar se username ou email já existem
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetchColumn() > 0) {
                return null; // Usuário já existe
            }

            // Criar novo usuário
            $passwordHash = User::hashPassword($password);
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (username, email, password_hash, full_name, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([$username, $email, $passwordHash, $fullName]);

            $userId = $this->pdo->lastInsertId();
            return $this->findUserById((int)$userId);

        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return null;
        }
    }

    public function findUserById(int $id): ?User
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            return $userData ? $this->createUserFromArray($userData) : null;
        } catch (PDOException $e) {
            error_log("Find user error: " . $e->getMessage());
            return null;
        }
    }

    public function findUserByUsername(string $username): ?User
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            return $userData ? $this->createUserFromArray($userData) : null;
        } catch (PDOException $e) {
            error_log("Find user error: " . $e->getMessage());
            return null;
        }
    }

    public function createSession(int $userId, string $ipAddress, string $userAgent): string
    {
        try {
            $sessionId = bin2hex(random_bytes(64));
            $expiresAt = new \DateTime('+24 hours');

            $stmt = $this->pdo->prepare(
                "INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $sessionId,
                $userId,
                $ipAddress,
                $userAgent,
                $expiresAt->format('Y-m-d H:i:s')
            ]);

            return $sessionId;
        } catch (PDOException $e) {
            error_log("Session creation error: " . $e->getMessage());
            throw new \RuntimeException("Failed to create session");
        }
    }

    public function validateSession(string $sessionId): ?User
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT s.*, u.* FROM user_sessions s 
                 JOIN users u ON s.user_id = u.id 
                 WHERE s.id = ? AND s.expires_at > NOW() AND u.status = 'active'"
            );
            $stmt->execute([$sessionId]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return null;
            }

            // Atualizar última atividade
            $this->updateSessionActivity($sessionId);

            return $this->createUserFromArray($data);
        } catch (PDOException $e) {
            error_log("Session validation error: " . $e->getMessage());
            return null;
        }
    }

    public function destroySession(string $sessionId): void
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE id = ?");
            $stmt->execute([$sessionId]);
        } catch (PDOException $e) {
            error_log("Session destruction error: " . $e->getMessage());
        }
    }

    public function destroyAllUserSessions(int $userId): void
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Session destruction error: " . $e->getMessage());
        }
    }

    private function handleFailedLogin(User $user, string $ipAddress, string $userAgent): void
    {
        try {
            $newAttempts = $user->getFailedLoginAttempts() + 1;
            $lockedUntil = null;

            if ($newAttempts >= $this->maxFailedAttempts) {
                $lockedUntil = new \DateTime("+{$this->lockoutDuration} seconds");
                $action = 'account_locked';
            } else {
                $action = 'login_failed';
            }

            $stmt = $this->pdo->prepare(
                "UPDATE users SET failed_login_attempts = ?, locked_until = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([
                $newAttempts,
                $lockedUntil ? $lockedUntil->format('Y-m-d H:i:s') : null,
                $user->getId()
            ]);

            $this->logAuthAttempt($user->getId(), $user->getUsername(), $action, $ipAddress, $userAgent, 
                "Failed attempt #{$newAttempts}");

        } catch (PDOException $e) {
            error_log("Failed login handling error: " . $e->getMessage());
        }
    }

    private function handleSuccessfulLogin(User $user, string $ipAddress, string $userAgent): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE users SET last_login = NOW(), failed_login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$user->getId()]);

            $this->logAuthAttempt($user->getId(), $user->getUsername(), 'login_success', $ipAddress, $userAgent);

        } catch (PDOException $e) {
            error_log("Successful login handling error: " . $e->getMessage());
        }
    }

    private function updateSessionActivity(string $sessionId): void
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$sessionId]);
        } catch (PDOException $e) {
            error_log("Session activity update error: " . $e->getMessage());
        }
    }

    private function logAuthAttempt(?int $userId, string $username, string $action, string $ipAddress, string $userAgent, ?string $details = null): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO auth_logs (user_id, username, action, ip_address, user_agent, details) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId,
                $username,
                $action,
                $ipAddress,
                $userAgent,
                $details ? json_encode(['message' => $details]) : null
            ]);
        } catch (PDOException $e) {
            error_log("Auth logging error: " . $e->getMessage());
        }
    }

    private function createUserFromArray(array $data): User
    {
        $user = new User(
            $data['username'],
            $data['email'],
            $data['password_hash'],
            $data['full_name']
        );

        $user->setId((int)$data['id']);
        $user->setStatus($data['status']);
        
        // Verificar se as colunas de autenticação existem (para compatibilidade com banco não migrado)
        $user->setEmailVerified(isset($data['email_verified']) ? (bool)$data['email_verified'] : false);
        $user->setFailedLoginAttempts(isset($data['failed_login_attempts']) ? (int)$data['failed_login_attempts'] : 0);

        if (isset($data['last_login']) && $data['last_login']) {
            $user->setLastLogin(new \DateTime($data['last_login']));
        }

        if (isset($data['locked_until']) && $data['locked_until']) {
            $user->setLockedUntil(new \DateTime($data['locked_until']));
        }

        if (isset($data['created_at']) && $data['created_at']) {
            $user->setCreatedAt(new \DateTime($data['created_at']));
        }

        if (isset($data['updated_at']) && $data['updated_at']) {
            $user->setUpdatedAt(new \DateTime($data['updated_at']));
        }

        return $user;
    }
}