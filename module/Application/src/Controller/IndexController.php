<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        // Redirecionar para o sistema de gerenciamento de tarefas
        return $this->redirect()->toRoute('task');
    }
}
