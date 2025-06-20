<?php

declare(strict_types=1);

namespace TaskManager\Controller;

use TaskManager\Service\TaskService;
use TaskManager\Validator\TaskBackendValidator;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Laminas\Http\Request;
use Laminas\Http\Response;

/**
 * Controller API dedicado para testes e integrações externas
 * Fornece endpoints REST puros com validação robusta
 * Extende AbstractRestfulController para garantir respostas JSON
 */
class ApiController extends AbstractRestfulController
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * GET /api/tasks/list - Lista todas as tarefas
     */
    public function listAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $userId = 1; // Usuário fixo para teste

        try {
            $page = (int) $this->params()->fromQuery('page', 1);
            $limit = (int) $this->params()->fromQuery('limit', 10);

            $filters = [
                'status' => $this->params()->fromQuery('status'),
                'priority' => $this->params()->fromQuery('priority'),
                'search' => $this->params()->fromQuery('search'),
            ];

            // Remover filtros vazios
            $filters = array_filter($filters, function ($value) {
                return $value !== null && $value !== '';
            });

            $result = $this->taskService->getUserTasksWithPagination($userId, $page, $limit, $filters);

            return $this->createJsonResponse([
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
        } catch (\Exception $e) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Erro ao buscar tarefas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/tasks/{id} - Obtém uma tarefa específica
     */
    public function getAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'ID da tarefa é obrigatório',
                'error_code' => 'MISSING_ID'
            ], 400);
        }

        try {
            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Tarefa não encontrada',
                    'error_code' => 'TASK_NOT_FOUND'
                ], 404);
            }

            return $this->createJsonResponse([
                'success' => true,
                'data' => $task->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Erro ao buscar tarefa: ' . $e->getMessage(),
                'error_code' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * POST /api/tasks/create - Cria uma nova tarefa
     */
    public function createAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();

        if (!$request instanceof Request || !$request->isPost()) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Método POST é obrigatório',
                'error_code' => 'INVALID_METHOD'
            ], 405);
        }

        try {
            // Obter dados do corpo da requisição (JSON ou form-data)
            $contentType = $request->getHeader('Content-Type');

            if ($contentType && strpos($contentType->getFieldValue(), 'application/json') !== false) {
                $rawBody = $request->getContent();
                $data = json_decode($rawBody, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->createJsonResponse([
                        'success' => false,
                        'message' => 'JSON inválido: ' . json_last_error_msg(),
                        'error_code' => 'INVALID_JSON'
                    ], 400);
                }
            } else {
                $data = $request->getPost()->toArray();
            }

            // Adicionar user_id fixo para teste
            $data['user_id'] = 1;

            // Sanitizar dados
            $sanitizedData = TaskBackendValidator::sanitize($data);

            // Validar dados
            $validationErrors = TaskBackendValidator::validate($sanitizedData, true);

            if (!empty($validationErrors)) {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validationErrors,
                    'error_code' => 'VALIDATION_ERROR'
                ], 400);
            }

            // Criar tarefa
            $task = $this->taskService->createTask($sanitizedData);

            return $this->createJsonResponse([
                'success' => true,
                'message' => 'Tarefa criada com sucesso',
                'data' => $task->toArray()
            ], 201);
        } catch (\Exception $e) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Erro ao criar tarefa: ' . $e->getMessage(),
                'error_code' => 'CREATION_ERROR'
            ], 500);
        }
    }

    /**
     * PUT /api/tasks/update/{id} - Atualiza uma tarefa
     */
    public function updateAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();

        if (!$request instanceof Request || (!$request->isPut() && !$request->isPost())) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Método PUT ou POST é obrigatório',
                'error_code' => 'INVALID_METHOD'
            ], 405);
        }

        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'ID da tarefa é obrigatório',
                'error_code' => 'MISSING_ID'
            ], 400);
        }

        try {
            // Verificar se a tarefa existe
            $existingTask = $this->taskService->getTaskById($id);
            if (!$existingTask) {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Tarefa não encontrada',
                    'error_code' => 'TASK_NOT_FOUND'
                ], 404);
            }

            // Verificar se a tarefa pode ser atualizada (status pending)
            $operationErrors = TaskBackendValidator::validateTaskUpdate($existingTask->getStatus());
            if (!empty($operationErrors)) {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Operação não permitida',
                    'errors' => ['operation' => $operationErrors],
                    'error_code' => 'OPERATION_NOT_ALLOWED'
                ], 403);
            }

            // Obter dados do corpo da requisição
            $contentType = $request->getHeader('Content-Type');

            if ($contentType && strpos($contentType->getFieldValue(), 'application/json') !== false) {
                $rawBody = $request->getContent();
                $data = json_decode($rawBody, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->createJsonResponse([
                        'success' => false,
                        'message' => 'JSON inválido: ' . json_last_error_msg(),
                        'error_code' => 'INVALID_JSON'
                    ], 400);
                }
            } else {
                $data = $request->getPost()->toArray();
            }

            // Sanitizar dados
            $sanitizedData = TaskBackendValidator::sanitize($data);

            // Validar dados (não é criação, então isCreate = false)
            $validationErrors = TaskBackendValidator::validate($sanitizedData, false);

            if (!empty($validationErrors)) {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Dados de entrada inválidos',
                    'errors' => $validationErrors,
                    'error_code' => 'VALIDATION_ERROR'
                ], 400);
            }

            // Atualizar tarefa
            $updatedTask = $this->taskService->updateTask($id, $sanitizedData);

            return $this->createJsonResponse([
                'success' => true,
                'message' => 'Tarefa atualizada com sucesso',
                'data' => $updatedTask->toArray()
            ]);
        } catch (\Exception $e) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Erro ao atualizar tarefa: ' . $e->getMessage(),
                'error_code' => 'UPDATE_ERROR'
            ], 500);
        }
    }

    /**
     * DELETE /api/tasks/delete/{id} - Exclui uma tarefa
     */
    public function deleteAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $request = $this->getRequest();

        if (!$request instanceof Request || (!$request->isDelete() && !$request->isPost())) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Método DELETE ou POST é obrigatório',
                'error_code' => 'INVALID_METHOD'
            ], 405);
        }

        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'ID da tarefa é obrigatório',
                'error_code' => 'MISSING_ID'
            ], 400);
        }

        try {
            // Verificar se a tarefa existe
            $task = $this->taskService->getTaskById($id);
            if (!$task) {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Tarefa não encontrada',
                    'error_code' => 'TASK_NOT_FOUND'
                ], 404);
            }

            // Verificar se a tarefa pode ser excluída (status pending e idade > 5 dias)
            $operationErrors = TaskBackendValidator::validateTaskDeletion(
                $task->getStatus(), 
                $task->getCreatedAt()
            );
            if (!empty($operationErrors)) {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Operação não permitida',
                    'errors' => ['operation' => $operationErrors],
                    'error_code' => 'OPERATION_NOT_ALLOWED'
                ], 403);
            }

            // Excluir tarefa
            $deleted = $this->taskService->deleteTask($id);

            if ($deleted) {
                return $this->createJsonResponse([
                    'success' => true,
                    'message' => 'Tarefa excluída com sucesso'
                ]);
            } else {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Erro ao excluir tarefa',
                    'error_code' => 'DELETE_ERROR'
                ], 500);
            }
        } catch (\Exception $e) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Erro ao excluir tarefa: ' . $e->getMessage(),
                'error_code' => 'DELETE_ERROR'
            ], 500);
        }
    }

    /**
     * Método helper para criar respostas JSON consistentes
     */
    private function createJsonResponse(array $data, int $statusCode = 200): Response
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response;
    }

    /**
     * POST /api/tasks/complete/{id} - Marca uma tarefa como concluída
     */
    public function completeAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'ID da tarefa é obrigatório',
                'error_code' => 'MISSING_ID'
            ], 400);
        }

        try {
            $task = $this->taskService->completeTask($id);

            if ($task) {
                return $this->createJsonResponse([
                    'success' => true,
                    'message' => 'Tarefa marcada como concluída',
                    'data' => $task->toArray()
                ]);
            } else {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Tarefa não encontrada',
                    'error_code' => 'TASK_NOT_FOUND'
                ], 404);
            }
        } catch (\Exception $e) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Erro ao completar tarefa: ' . $e->getMessage(),
                'error_code' => 'COMPLETE_ERROR'
            ], 500);
        }
    }

    /**
     * POST /api/tasks/start/{id} - Marca uma tarefa como em andamento
     */
    public function startAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'ID da tarefa é obrigatório',
                'error_code' => 'MISSING_ID'
            ], 400);
        }

        try {
            $task = $this->taskService->startTask($id);

            if ($task) {
                return $this->createJsonResponse([
                    'success' => true,
                    'message' => 'Tarefa marcada como em andamento',
                    'data' => $task->toArray()
                ]);
            } else {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Tarefa não encontrada',
                    'error_code' => 'TASK_NOT_FOUND'
                ], 404);
            }
        } catch (\Exception $e) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Erro ao iniciar tarefa: ' . $e->getMessage(),
                'error_code' => 'START_ERROR'
            ], 500);
        }
    }

    /**
     * POST /api/tasks/duplicate/{id} - Duplica uma tarefa
     */
    public function duplicateAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'ID da tarefa é obrigatório',
                'error_code' => 'MISSING_ID'
            ], 400);
        }

        try {
            $task = $this->taskService->duplicateTask($id);

            if ($task) {
                return $this->createJsonResponse([
                    'success' => true,
                    'message' => 'Tarefa duplicada com sucesso',
                    'data' => $task->toArray()
                ], 201);
            } else {
                return $this->createJsonResponse([
                    'success' => false,
                    'message' => 'Tarefa não encontrada',
                    'error_code' => 'TASK_NOT_FOUND'
                ], 404);
            }
        } catch (\Exception $e) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Erro ao duplicar tarefa: ' . $e->getMessage(),
                'error_code' => 'DUPLICATE_ERROR'
            ], 500);
        }
    }

    /**
     * GET /api/tasks/statistics - Retorna estatísticas das tarefas
     */
    public function statisticsAction()
    {
        $this->getResponse()->getHeaders()->addHeaderLine('Content-Type', 'application/json');

        $userId = 1; // Usuário fixo para teste

        try {
            $statistics = $this->taskService->getTaskStatistics($userId);
            $overdueTasks = $this->taskService->getOverdueTasks($userId);

            $data = array_merge($statistics, [
                'overdue_tasks' => array_map(function ($task) {
                    return $task->toArray();
                }, $overdueTasks)
            ]);

            return $this->createJsonResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->createJsonResponse([
                'success' => false,
                'message' => 'Erro ao buscar estatísticas: ' . $e->getMessage(),
                'error_code' => 'STATISTICS_ERROR'
            ], 500);
        }
    }
}
