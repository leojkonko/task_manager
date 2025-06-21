<?php

declare(strict_types=1);

namespace Auth\Controller;

use Auth\Service\AuthService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Request;
use Laminas\View\Model\JsonModel;

class AuthController extends AbstractActionController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Página de login
     */
    public function loginAction()
    {
        $request = $this->getRequest();
        $messages = [];

        if ($request instanceof Request && $request->isPost()) {
            $postData = $request->getPost()->toArray();
            $username = trim($postData['username'] ?? '');
            $password = $postData['password'] ?? '';

            if (empty($username) || empty($password)) {
                $messages['error'] = 'Please fill in all fields.';
            } else {
                $ipAddress = $this->getClientIp();
                $userAgent = $request->getHeader('User-Agent')->toString();

                $user = $this->authService->authenticate($username, $password, $ipAddress, $userAgent);

                if ($user) {
                    // Criar sessão
                    $sessionId = $this->authService->createSession($user->getId(), $ipAddress, $userAgent);

                    // Definir cookie de sessão
                    setcookie('auth_session', $sessionId, [
                        'expires' => time() + 86400, // 24 horas
                        'path' => '/',
                        'domain' => '',
                        'secure' => isset($_SERVER['HTTPS']),
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);

                    // Se for requisição AJAX
                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => true,
                            'message' => 'Login successful',
                            'redirect' => $this->url()->fromRoute('task-manager')
                        ]);
                    }

                    $this->flashMessenger()->addSuccessMessage('Welcome, ' . $user->getFullName() . '!');
                    return $this->redirect()->toRoute('task-manager');
                } else {
                    $messages['error'] = 'Invalid username or password.';
                }
            }
        }

        return new ViewModel([
            'messages' => $messages
        ]);
    }

    /**
     * Página de registro
     */
    public function registerAction()
    {
        $request = $this->getRequest();
        $messages = [];

        if ($request instanceof Request && $request->isPost()) {
            $postData = $request->getPost()->toArray();

            $username = trim($postData['username'] ?? '');
            $email = trim($postData['email'] ?? '');
            $password = $postData['password'] ?? '';
            $confirmPassword = $postData['confirm_password'] ?? '';
            $fullName = trim($postData['full_name'] ?? '');

            // Basic validations
            $errors = [];

            if (empty($username)) {
                $errors[] = 'Username is required.';
            } elseif (strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters long.';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errors[] = 'Username must contain only letters, numbers and underscore.';
            }

            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email address.';
            }

            if (empty($password)) {
                $errors[] = 'Password is required.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($fullName)) {
                $errors[] = 'Full name is required.';
            }

            if (empty($errors)) {
                $user = $this->authService->register($username, $email, $password, $fullName);

                if ($user) {
                    // Se for requisição AJAX
                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => true,
                            'message' => 'Registration successful',
                            'redirect' => $this->url()->fromRoute('auth/login')
                        ]);
                    }

                    $this->flashMessenger()->addSuccessMessage('Registration successful! You can now sign in.');
                    return $this->redirect()->toRoute('auth/login');
                } else {
                    $messages['error'] = 'Username or email already exists. Please try with different details.';
                }
            } else {
                $messages['error'] = implode(' ', $errors);
            }
        }

        return new ViewModel([
            'messages' => $messages
        ]);
    }

    /**
     * Logout do usuário
     */
    public function logoutAction()
    {
        $sessionId = $_COOKIE['auth_session'] ?? null;

        if ($sessionId) {
            $this->authService->destroySession($sessionId);
        }

        // Remover cookie
        setcookie('auth_session', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        $this->flashMessenger()->addSuccessMessage('Logout successful!');
        return $this->redirect()->toRoute('auth/login');
    }

    /**
     * Obtém o IP do cliente
     */
    private function getClientIp(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];

                // Se há múltiplos IPs, pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validar IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
