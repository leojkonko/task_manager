<?php

declare(strict_types=1);

namespace TaskManager\Controller;

use TaskManager\Service\TaskService;
use TaskManager\Entity\Task;
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

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Lista todas as tarefas do usuário
     */
    public function indexAction()
    {
        // Por enquanto, vamos usar um usuário fixo para teste
        // Em uma implementação real, o ID do usuário viria da sessão/autenticação
        $userId = 1;

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
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $result = $this->taskService->getUserTasksWithPagination($userId, $page, $limit, $filters);

        // Se for uma requisição AJAX, retornar JSON
        $request = $this->getRequest();
        if ($request instanceof Request && $request->getHeader('X-Requested-With')) {
            return new JsonModel([
                'success' => true,
                'data' => [
                    'tasks' => array_map(function($task) {
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
        $tasks = array_map(function($task) {
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
        ]);
    }

    /**
     * Exibe uma tarefa específica
     */
    public function viewAction()
    {
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
            
            $this->flashMessenger()->addErrorMessage('Tarefa não encontrada');
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
        ]);
    }

    /**
     * Formulário para criar nova tarefa
     */
    public function createAction()
    {
        $request = $this->getRequest();
        
        if ($request instanceof Request && $request->isPost()) {
            $data = $request->getPost()->toArray();
            $data['user_id'] = 1; // Usuário fixo para teste
            
            try {
                $task = $this->taskService->createTask($data);
                
                if ($request->getHeader('X-Requested-With')) {
                    return new JsonModel([
                        'success' => true,
                        'message' => 'Tarefa criada com sucesso',
                        'data' => $task->toArray()
                    ]);
                }
                
                $this->flashMessenger()->addSuccessMessage('Tarefa criada com sucesso');
                return $this->redirect()->toRoute('task-manager/view', ['id' => $task->getId()]);
                
            } catch (\InvalidArgumentException $e) {
                if ($request->getHeader('X-Requested-With')) {
                    return new JsonModel([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                if ($request->getHeader('X-Requested-With')) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Erro interno do servidor'
                    ]);
                }
                
                $this->flashMessenger()->addErrorMessage('Erro ao criar tarefa');
            }
        }

        return new ViewModel([
            'availableStatuses' => Task::getAvailableStatuses(),
            'availablePriorities' => Task::getAvailablePriorities(),
        ]);
    }

    /**
     * Formulário para editar tarefa
     */
    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('task-manager');
        }

        $task = $this->taskService->getTaskById($id);
        
        if (!$task) {
            $this->flashMessenger()->addErrorMessage('Tarefa não encontrada');
            return $this->redirect()->toRoute('task-manager');
        }

        $request = $this->getRequest();
        
        if ($request instanceof Request && $request->isPost()) {
            $data = $request->getPost()->toArray();
            
            try {
                $updatedTask = $this->taskService->updateTask($id, $data);
                
                if ($request->getHeader('X-Requested-With')) {
                    return new JsonModel([
                        'success' => true,
                        'message' => 'Tarefa atualizada com sucesso',
                        'data' => $updatedTask->toArray()
                    ]);
                }
                
                $this->flashMessenger()->addSuccessMessage('Tarefa atualizada com sucesso');
                return $this->redirect()->toRoute('task-manager/view', ['id' => $id]);
                
            } catch (\InvalidArgumentException $e) {
                if ($request->getHeader('X-Requested-With')) {
                    return new JsonModel([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
                
                $this->flashMessenger()->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                if ($request->getHeader('X-Requested-With')) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Erro interno do servidor'
                    ]);
                }
                
                $this->flashMessenger()->addErrorMessage('Erro ao atualizar tarefa');
            }
        }

        return new ViewModel([
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
            
            $this->flashMessenger()->addErrorMessage('Tarefa não encontrada');
            return $this->redirect()->toRoute('task-manager');
        }

        $request = $this->getRequest();
        
        if ($request instanceof Request && $request->isPost()) {
            try {
                $deleted = $this->taskService->deleteTask($id);
                
                if ($deleted) {
                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => true,
                            'message' => 'Tarefa excluída com sucesso'
                        ]);
                    }
                    
                    $this->flashMessenger()->addSuccessMessage('Tarefa excluída com sucesso');
                } else {
                    if ($request->getHeader('X-Requested-With')) {
                        return new JsonModel([
                            'success' => false,
                            'message' => 'Erro ao excluir tarefa'
                        ]);
                    }
                    
                    $this->flashMessenger()->addErrorMessage('Erro ao excluir tarefa');
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
                'overdue_tasks' => array_map(function($task) {
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
}
