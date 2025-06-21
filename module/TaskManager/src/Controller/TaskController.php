<?php

declare(strict_types=1);

namespace TaskManager\Controller;

use TaskManager\Service\TaskService;
use TaskManager\Entity\Task;
use TaskManager\Form\TaskForm;
use TaskManager\Validator\TaskBackendValidator;
use TaskManager\Helper\MessageHelper;
use Auth\Service\AuthenticationManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Laminas\Http\Request;

/**
 * Controller para gerenciar as operações CRUD das tarefas
 */
class TaskController extends AbstractActionController
{
    private TaskService $taskService;
    private AuthenticationManager $authManager;

    public function __construct(TaskService $taskService, AuthenticationManager $authManager)
    {
        $this->taskService = $taskService;
        $this->authManager = $authManager;
    }

    /**
     * Verifica se o usuário está autenticado
     */
    private function requireAuthentication()
    {
        if (!$this->authManager->isLoggedIn()) {
            $this->flashMessenger()->addErrorMessage('You need to be logged in to access this page.');
            return $this->redirect()->toRoute('auth/login');
        }
        return true;
    }

    /**
     * Obtém o ID do usuário atual
     */
    private function getCurrentUserId(): int
    {
        $user = $this->authManager->getCurrentUser();
        if (!$user) {
            throw new \RuntimeException('Usuário não autenticado');
        }
        return $user->getId();
    }

    /**
     * Lista todas as tarefas do usuário
     */
    public function indexAction()
    {
        $authCheck = $this->requireAuthentication();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $userId = $this->getCurrentUserId();

        $page = (int) $this->params()->fromQuery('page', 1);
        $limit = (int) $this->params()->fromQuery('limit', 10);

        $filters = [
            'status' => $this->params()->fromQuery('status'),
            'priority' => $this->params()->fromQuery('priority'),
            'category_id' => $this->params()->fromQuery('category_id'),
            'search' => $this->params()->fromQuery('search'),
            'order_by' => $this->params()->fromQuery('order_by', 'created_at'),
            'order_direction' => $this->params()->fromQuery('order_direction', 'DESC'),
        ];

        // Remover filtros vazios
        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        $result = $this->taskService->getUserTasksWithPagination($userId, $page, $limit, $filters);

        // Se for uma requisição AJAX, retornar JSON
        $request = $this->getRequest();
        if ($request instanceof Request && $request->getHeader('X-Requested-With')) {
            return new JsonModel([
                'success' => true,
                'data' => [
                    'tasks' => array_map(function ($task) {
                        return $task->toArray();
                    }, $result['tasks']),
                    'pagination' => [
                        'total' => $result['total'],
                        'page' => $result['page'],
                        'limit' => $result['limit'],
                        'total_pages' => $result['total_pages'],
                    ]
                ]
            ]);
        }

        // Converter tasks para array para a view
        $tasks = array_map(function ($task) {
            return $task->toArray();
        }, $result['tasks']);

        return new ViewModel([
            'tasks' => $tasks,
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total_pages' => $result['total_pages'],
            ],
            'filters' => $filters,
            'availableStatuses' => Task::getAvailableStatuses(),
            'availablePriorities' => Task::getAvailablePriorities(),
            'currentUser' => $this->authManager->getCurrentUser()->toArray(),
        ]);
    }

    /**
     * Exibe uma tarefa específica
     */
    public function viewAction()
    {
        $authCheck = $this->requireAuthentication();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute('task-manager');
        }

        $task = $this->taskService->getTaskById($id);

        if (!$task) {
            $request = $this->getRequest();
            if ($request instanceof Request && $request->getHeader('X-Requested-With')) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }

            $this->flashMessenger()->addErrorMessage(MessageHelper::getErrorMessage('not_found'));
            return $this->redirect()->toRoute('task-manager');
        }

        // Verificar se a tarefa pertence ao usuário atual
        if ($task->getUserId() !== $this->getCurrentUserId()) {
            $this->flashMessenger()->addErrorMessage('You do not have permission to view this task.');
            return $this->redirect()->toRoute('task-manager');
        }

        $request = $this->getRequest();
        if ($request instanceof Request && $request->getHeader('X-Requested-With')) {
            return new JsonModel([
                'success' => true,
                'data' => $task->toArray()
            ]);
        }

        return new ViewModel([
            'task' => $task->toArray(),
            'availableStatuses' => Task::getAvailableStatuses(),
            'availablePriorities' => Task::getAvailablePriorities(),
            'currentUser' => $this->authManager->getCurrentUser()->toArray(),
        ]);
    }

    /**
     * Formulário para criar nova tarefa
     */
    public function createAction()
    {
        $authCheck = $this->requireAuthentication();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $form = new TaskForm();
        $form->get('submit')->setValue('Criar Tarefa');

        // Definir valores padrão
        $form->get('status')->setValue(Task::STATUS_PENDING);
        $form->get('priority')->setValue(Task::PRIORITY_MEDIUM);

        $request = $this->getRequest();

        if ($request instanceof Request && $request->isPost()) {
            $postData = $request->getPost()->toArray();

            // Primeiro: sanitizar dados de entrada
            $sanitizedData = TaskBackendValidator::sanitize($postData);

            // Segundo: validar dados no backend
            $validationErrors = TaskBackendValidator::validate($sanitizedData, true);

            if (!empty($validationErrors)) {
                // Se há erros de validação, retornar resposta apropriada
                if ($request->getHeader('X-Requested-With')) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Dados inválidos fornecidos',
                        'errors' => $validationErrors
                    ]);
                }

                // Para requisições normais, adicionar erros ao formulário
                foreach ($validationErrors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->flashMessenger()->addErrorMessage($error);
                    }
                }

                $form->setData($sanitizedData);
                return new ViewModel([
                    'form' => $form,
                    'validationErrors' => $validationErrors,
                    'availableStatuses' => Task::getAvailableStatuses(),
                    'availablePriorities' => Task::getAvailablePriorities(),
                ]);
            }

            // Dados válidos: continuar com validação do formulário Laminas
            $form->setData($sanitizedData);

            if ($form->isValid()) {
                try {
                    $data = $form->getTaskData();
                    $data['user_id'] = $this->getCurrentUserId(); // Usar usuário autenticado

                    // Validação final antes de criar
                    TaskBackendValidator::validateAndThrow($data, true);

                    $task = $this->taskService->createTask($data);

                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => true,
                            'message' => 'Tarefa criada com sucesso',
                            'data' => $task->toArray()
                        ]);
                    }

                    $this->flashMessenger()->addSuccessMessage(MessageHelper::getSuccessMessage('create'));
                    return $this->redirect()->toRoute('task-manager/view', ['id' => $task->getId()]);
                } catch (\Exception $e) {
                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => false,
                            'message' => $e->getMessage()
                        ]);
                    }

                    // Verificar se é erro específico e usar mensagem amigável
                    $errorMessage = $this->getCustomErrorMessage($e->getMessage());
                    $this->flashMessenger()->addErrorMessage($errorMessage);
                }
            } else {
                // Formulário inválido - exibir erros
                if ($request->getHeader('X-Requested-With')) {
                    $errors = [];
                    foreach ($form->getMessages() as $field => $fieldErrors) {
                        $errors[$field] = array_values($fieldErrors);
                    }

                    return new JsonModel([
                        'success' => false,
                        'message' => 'Dados do formulário inválidos',
                        'errors' => $errors
                    ]);
                }
            }
        }

        return new ViewModel([
            'form' => $form,
            'availableStatuses' => Task::getAvailableStatuses(),
            'availablePriorities' => Task::getAvailablePriorities(),
        ]);
    }

    /**
     * Formulário para editar tarefa
     */
    public function editAction()
    {
        $authCheck = $this->requireAuthentication();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute('task-manager');
        }

        $task = $this->taskService->getTaskById($id);

        if (!$task) {
            $this->flashMessenger()->addErrorMessage(MessageHelper::getErrorMessage('not_found'));
            return $this->redirect()->toRoute('task-manager');
        }

        // Verificar se a tarefa pertence ao usuário atual
        if ($task->getUserId() !== $this->getCurrentUserId()) {
            $this->flashMessenger()->addErrorMessage('Você não tem permissão para editar esta tarefa.');
            return $this->redirect()->toRoute('task-manager');
        }

        // VERIFICAR PRIMEIRO SE A TAREFA PODE SER EDITADA (ANTES DE MOSTRAR O FORMULÁRIO)
        $operationErrors = TaskBackendValidator::validateTaskUpdate($task->getStatus());
        if (!empty($operationErrors)) {
            // Se não pode editar, mostrar erro e redirecionar
            foreach ($operationErrors as $error) {
                $this->flashMessenger()->addErrorMessage($error);
            }
            return $this->redirect()->toRoute('task-manager/view', ['id' => $id]);
        }

        $form = new TaskForm();
        $form->get('submit')->setValue('Atualizar Tarefa');
        $form->populateFromTask($task);

        $request = $this->getRequest();

        if ($request instanceof Request && $request->isPost()) {
            $postData = $request->getPost()->toArray();

            // Primeiro: sanitizar dados de entrada
            $sanitizedData = TaskBackendValidator::sanitize($postData);

            // Verificar novamente se a tarefa pode ser atualizada (dupla verificação para segurança)
            $operationErrors = TaskBackendValidator::validateTaskUpdate($task->getStatus());
            if (!empty($operationErrors)) {
                if ($request->getHeader('X-Requested-With')) {
                    // Para requisições AJAX, usar a primeira mensagem específica
                    $mainError = reset($operationErrors);
                    return new JsonModel([
                        'success' => false,
                        'message' => $mainError,
                        'errors' => ['operation' => $operationErrors]
                    ]);
                }

                foreach ($operationErrors as $error) {
                    $this->flashMessenger()->addErrorMessage($error);
                }

                return $this->redirect()->toRoute('task-manager/view', ['id' => $id]);
            }

            // Segundo: validar dados no backend (não é criação, então isCreate = false)
            $validationErrors = TaskBackendValidator::validate($sanitizedData, false);

            if (!empty($validationErrors)) {
                // Se há erros de validação, retornar resposta apropriada
                if ($request->getHeader('X-Requested-With')) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Dados inválidos fornecidos',
                        'errors' => $validationErrors
                    ]);
                }

                // Para requisições normais, adicionar erros ao formulário
                foreach ($validationErrors as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $this->flashMessenger()->addErrorMessage($error);
                    }
                }

                $form->setData($sanitizedData);
                return new ViewModel([
                    'form' => $form,
                    'task' => $task->toArray(),
                    'validationErrors' => $validationErrors,
                    'availableStatuses' => Task::getAvailableStatuses(),
                    'availablePriorities' => Task::getAvailablePriorities(),
                ]);
            }

            $form->setData($sanitizedData);

            if ($form->isValid()) {
                try {
                    $data = $form->getTaskData();

                    // Validação final antes de atualizar
                    TaskBackendValidator::validateAndThrow($data, false);

                    $updatedTask = $this->taskService->updateTask($id, $data);

                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => true,
                            'message' => 'Tarefa atualizada com sucesso',
                            'data' => $updatedTask->toArray()
                        ]);
                    }

                    $this->flashMessenger()->addSuccessMessage(MessageHelper::getSuccessMessage('update'));
                    return $this->redirect()->toRoute('task-manager/view', ['id' => $id]);
                } catch (\Exception $e) {
                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => false,
                            'message' => $e->getMessage()
                        ]);
                    }

                    $errorMessage = $this->getCustomErrorMessage($e->getMessage());
                    $this->flashMessenger()->addErrorMessage($errorMessage);
                }
            } else {
                // Formulário inválido - exibir erros
                if ($request->getHeader('X-Requested-With')) {
                    $errors = [];
                    foreach ($form->getMessages() as $field => $fieldErrors) {
                        $errors[$field] = array_values($fieldErrors);
                    }

                    return new JsonModel([
                        'success' => false,
                        'message' => 'Dados do formulário inválidos',
                        'errors' => $errors
                    ]);
                }
            }
        }

        return new ViewModel([
            'form' => $form,
            'task' => $task->toArray(),
            'availableStatuses' => Task::getAvailableStatuses(),
            'availablePriorities' => Task::getAvailablePriorities(),
        ]);
    }

    /**
     * Exclui uma tarefa
     */
    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            $request = $this->getRequest();
            if ($request instanceof Request && $request->getHeader('X-Requested-With')) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'ID da tarefa não fornecido'
                ]);
            }
            return $this->redirect()->toRoute('task-manager');
        }

        // Verificar se a tarefa existe
        $task = $this->taskService->getTaskById($id);
        if (!$task) {
            $request = $this->getRequest();
            if ($request instanceof Request && $request->getHeader('X-Requested-With')) {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }

            $this->flashMessenger()->addErrorMessage(MessageHelper::getErrorMessage('not_found'));
            return $this->redirect()->toRoute('task-manager');
        }

        $request = $this->getRequest();

        if ($request instanceof Request && $request->isPost()) {
            // Verificar se a tarefa pode ser excluída (status pending e idade > 5 dias)
            $operationErrors = TaskBackendValidator::validateTaskDeletion(
                $task->getStatus(),
                $task->getCreatedAt()
            );
            if (!empty($operationErrors)) {
                if ($request->getHeader('X-Requested-With')) {
                    // Para requisições AJAX, usar a primeira mensagem específica
                    $mainError = reset($operationErrors);
                    return new JsonModel([
                        'success' => false,
                        'message' => $mainError,
                        'errors' => ['operation' => $operationErrors]
                    ]);
                }

                // Para requisições normais, mostrar cada erro específico
                foreach ($operationErrors as $error) {
                    $this->flashMessenger()->addErrorMessage($error);
                }

                return $this->redirect()->toRoute('task-manager');
            }

            try {
                $deleted = $this->taskService->deleteTask($id);

                if ($deleted) {
                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => true,
                            'message' => 'Tarefa excluída com sucesso'
                        ]);
                    }

                    $this->flashMessenger()->addSuccessMessage(MessageHelper::getSuccessMessage('delete'));
                } else {
                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => false,
                            'message' => 'Erro ao excluir tarefa'
                        ]);
                    }

                    $this->flashMessenger()->addErrorMessage(MessageHelper::getErrorMessage('general_error'));
                }
            } catch (\Exception $e) {
                if ($request->getHeader('X-Requested-With')) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Erro interno do servidor'
                    ]);
                }

                $this->flashMessenger()->addErrorMessage('Erro ao excluir tarefa');
            }

            return $this->redirect()->toRoute('task-manager');
        }

        return new ViewModel([
            'task' => $task->toArray()
        ]);
    }

    /**
     * Marca uma tarefa como concluída
     */
    public function completeAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return new JsonModel([
                'success' => false,
                'message' => 'ID da tarefa não fornecido'
            ]);
        }

        try {
            $task = $this->taskService->completeTask($id);

            if ($task) {
                return new JsonModel([
                    'success' => true,
                    'message' => 'Tarefa marcada como concluída',
                    'data' => $task->toArray()
                ]);
            } else {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao completar tarefa'
            ]);
        }
    }

    /**
     * Marca uma tarefa como em andamento
     */
    public function startAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return new JsonModel([
                'success' => false,
                'message' => 'ID da tarefa não fornecido'
            ]);
        }

        try {
            $task = $this->taskService->startTask($id);

            if ($task) {
                return new JsonModel([
                    'success' => true,
                    'message' => 'Tarefa marcada como em andamento',
                    'data' => $task->toArray()
                ]);
            } else {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao iniciar tarefa'
            ]);
        }
    }

    /**
     * Duplica uma tarefa
     */
    public function duplicateAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return new JsonModel([
                'success' => false,
                'message' => 'ID da tarefa não fornecido'
            ]);
        }

        try {
            $task = $this->taskService->duplicateTask($id);

            if ($task) {
                return new JsonModel([
                    'success' => true,
                    'message' => 'Tarefa duplicada com sucesso',
                    'data' => $task->toArray()
                ]);
            } else {
                return new JsonModel([
                    'success' => false,
                    'message' => 'Tarefa não encontrada'
                ]);
            }
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao duplicar tarefa'
            ]);
        }
    }

    /**
     * Retorna estatísticas das tarefas
     */
    public function statisticsAction()
    {
        $userId = 1; // Usuário fixo para teste

        try {
            $statistics = $this->taskService->getTaskStatistics($userId);
            $overdueTasks = $this->taskService->getOverdueTasks($userId);

            $data = array_merge($statistics, [
                'overdue_tasks' => array_map(function ($task) {
                    return $task->toArray();
                }, $overdueTasks)
            ]);

            return new JsonModel([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'success' => false,
                'message' => 'Erro ao buscar estatísticas'
            ]);
        }
    }

    /**
     * Helper para converter mensagens de erro técnicas em mensagens amigáveis
     */
    private function getCustomErrorMessage(string $technicalMessage): string
    {
        // Verificar se contém mensagens específicas conhecidas
        if (strpos($technicalMessage, 'fim de semana') !== false) {
            return MessageHelper::getErrorMessage('weekday_only');
        }

        if (strpos($technicalMessage, 'pending') !== false && strpos($technicalMessage, 'atualizada') !== false) {
            return MessageHelper::getErrorMessage('pending_only_update');
        }

        if (strpos($technicalMessage, 'pending') !== false && strpos($technicalMessage, 'excluída') !== false) {
            return MessageHelper::getErrorMessage('pending_only_delete');
        }

        if (strpos($technicalMessage, '5 dias') !== false) {
            return MessageHelper::getErrorMessage('too_recent_delete');
        }

        // Retornar mensagem genérica se não for erro conhecido
        return MessageHelper::getErrorMessage('general_error');
    }
}
