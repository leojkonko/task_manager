<?php

declare(strict_types=1);

namespace Auth\View\Helper;

use Auth\Service\AuthenticationManager;
use Auth\Model\User;
use Laminas\View\Helper\AbstractHelper;

class Identity extends AbstractHelper
{
    private AuthenticationManager $authManager;

    public function __construct(AuthenticationManager $authManager)
    {
        $this->authManager = $authManager;
    }

    /**
     * Retorna o usuário atual ou null se não estiver logado
     */
    public function __invoke(): ?User
    {
        return $this->authManager->getCurrentUser();
    }

    /**
     * Verifica se o usuário está logado
     */
    public function isLoggedIn(): bool
    {
        return $this->authManager->isLoggedIn();
    }
}
