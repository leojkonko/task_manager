<?php

declare(strict_types=1);

namespace Application\Controller;

use Auth\Service\AuthenticationManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    private AuthenticationManager $authManager;

    public function __construct(AuthenticationManager $authManager)
    {
        $this->authManager = $authManager;
    }

    public function indexAction()
    {
        // Verificar se o usuário está logado
        if ($this->authManager->isLoggedIn()) {
            // Se estiver logado, redirecionar para o sistema de tarefas
            return $this->redirect()->toRoute('task-manager');
        } else {
            // Se não estiver logado, redirecionar para o login
            return $this->redirect()->toRoute('auth/login');
        }
    }
}
