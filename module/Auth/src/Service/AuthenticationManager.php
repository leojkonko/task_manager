<?php

declare(strict_types=1);

namespace Auth\Service;

use Auth\Model\User;
use Auth\Service\AuthService;

class AuthenticationManager
{
    private AuthService $authService;
    private ?User $currentUser = null;
    private bool $initialized = false;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function getCurrentUser(): ?User
    {
        if (!$this->initialized) {
            $this->initializeCurrentUser();
        }

        return $this->currentUser;
    }

    public function isLoggedIn(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    public function requireLogin(): bool
    {
        if (!$this->isLoggedIn()) {
            // Redirecionar para login será feito no controller
            return false;
        }
        return true;
    }

    public function hasPermission(string $permission): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        // Por enquanto, todos os usuários autenticados têm todas as permissões
        // Isso pode ser expandido no futuro com um sistema de roles/permissions
        return true;
    }

    public function login(string $username, string $password, string $ipAddress, string $userAgent): ?User
    {
        $user = $this->authService->authenticate($username, $password, $ipAddress, $userAgent);

        if ($user) {
            $this->currentUser = $user;
            $this->initialized = true;
        }

        return $user;
    }

    public function logout(): void
    {
        $sessionId = $_COOKIE['auth_session'] ?? null;

        if ($sessionId) {
            $this->authService->destroySession($sessionId);
        }

        $this->currentUser = null;
        $this->initialized = true;

        // Remover cookie
        setcookie('auth_session', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    private function initializeCurrentUser(): void
    {
        $this->initialized = true;

        $sessionId = $_COOKIE['auth_session'] ?? null;

        if (!$sessionId) {
            return;
        }

        $user = $this->authService->validateSession($sessionId);

        if ($user && $user->isActive() && !$user->isLocked()) {
            $this->currentUser = $user;
        } else {
            // Sessão inválida, remover cookie
            $this->logout();
        }
    }
}
