<?php

declare(strict_types=1);

namespace TaskManagerTest\Controller;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TaskManager\Controller\TaskController;
use TaskManager\Service\TaskService;
use TaskManager\Entity\Task;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\Plugin\FlashMessenger;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\View\Model\ViewModel;
use Laminas\Stdlib\Parameters;
use DateTime;

/**
 * Testes unitários para TaskController
 * 
 * Testa todas as ações do controller:
 * - Listagem de tarefas (indexAction)
 * - Criação de tarefas (createAction)
 * - Edição de tarefas (editAction)
 * - Visualização de tarefas (viewAction)
 * - Exclusão de tarefas (deleteAction)
 * - Operações AJAX (complete, duplicate)
 */
class TaskControllerTest extends TestCase
{
    private TaskController $controller;
    private MockObject $taskService;
    private MockObject $request;
    private MockObject $response;
    private MockObject $flashMessenger;
    private MockObject $redirect;

    protected function setUp(): void
    {
        $this->taskService = $this->createMock(TaskService::class);
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->flashMessenger = $this->createMock(FlashMessenger::class);
        $this->redirect = $this->createMock(Redirect::class);

        $this->controller = new TaskController($this->taskService);
        $this->controller->setRequest($this->request);
        $this->controller->setResponse($this->response);

        // Mock dos plugins
        $this->controller->getPluginManager()->setService('flashMessenger', $this->flashMessenger);
        $this->controller->getPluginManager()->setService('redirect', $this->redirect);
    }

    /**
     * Testa listagem de tarefas sem filtros
     */
    public function testIndexActionWithoutFilters(): void
    {
        $expectedTasks = [
            new Task(),
            new Task()
        ];

        $expectedPagination = [
            'page' => 1,
            'limit' => 10,
            'total' => 2,
            'total_pages' => 1
        ];

        $serviceResult = [
            'tasks' => $expectedTasks,
            'pagination' => $expectedPagination
        ];

        $this->request
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn(new Parameters([]));

        $this->taskService
            ->expects($this->once())
            ->method('getTasks')
            ->with([], 1, 10, 1) // userId mockado como 1
            ->willReturn($serviceResult);

        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($expectedTasks, $result->getVariable('tasks'));
        $this->assertEquals($expectedPagination, $result->getVariable('pagination'));
    }

    /**
     * Testa listagem de tarefas com filtros
     */
    public function testIndexActionWithFilters(): void
    {
        $filters = [
            'status' => 'pending',
            'priority' => 'high',
            'search' => 'importante'
        ];

        $queryParams = new Parameters($filters + ['page' => '2']);

        $this->request
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($queryParams);

        $this->taskService
            ->expects($this->once())
            ->method('getTasks')
            ->with($filters, 2, 10, 1)
            ->willReturn([
                'tasks' => [],
                'pagination' => ['page' => 2, 'limit' => 10, 'total' => 0, 'total_pages' => 0]
            ]);

        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($filters, $result->getVariable('filters'));
    }

    /**
     * Testa exibição do formulário de criação (GET)
     */
    public function testCreateActionGet(): void
    {
        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(false);

        $result = $this->controller->createAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertArrayHasKey('form', $result->getVariables());
    }

    /**
     * Testa criação de tarefa válida (POST)
     */
    public function testCreateActionPostValid(): void
    {
        $postData = [
            'title' => 'Nova tarefa',
            'description' => 'Descrição da tarefa',
            'priority' => 'high',
            'due_date' => '2025-12-31',
            'category_id' => 1
        ];

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('getPost')
            ->willReturn(new Parameters($postData));

        $this->taskService
            ->expects($this->once())
            ->method('createTask')
            ->with(array_merge($postData, ['user_id' => 1]))
            ->willReturn([
                'success' => true,
                'id' => 123,
                'message' => 'Tarefa criada com sucesso'
            ]);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addSuccessMessage')
            ->with('✅ Tarefa criada com sucesso!');

        $this->redirect
            ->expects($this->once())
            ->method('toRoute')
            ->with('task-manager/view', ['id' => 123]);

        $this->controller->createAction();
    }

    /**
     * Testa criação de tarefa inválida (POST)
     */
    public function testCreateActionPostInvalid(): void
    {
        $postData = [
            'title' => '', // Título vazio
            'description' => 'Descrição'
        ];

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('getPost')
            ->willReturn(new Parameters($postData));

        $this->taskService
            ->expects($this->once())
            ->method('createTask')
            ->willReturn([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => ['title' => ['Título é obrigatório']]
            ]);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with('❌ Erro ao criar tarefa: Dados inválidos');

        $result = $this->controller->createAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($postData, $result->getVariable('formData'));
        $this->assertEquals(['title' => ['Título é obrigatório']], $result->getVariable('errors'));
    }

    /**
     * Testa criação durante fim de semana
     */
    public function testCreateActionOnWeekend(): void
    {
        $postData = [
            'title' => 'Tarefa de fim de semana',
            'description' => 'Tentativa no sábado'
        ];

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('getPost')
            ->willReturn(new Parameters($postData));

        $this->taskService
            ->expects($this->once())
            ->method('createTask')
            ->willReturn([
                'success' => false,
                'message' => 'Tarefas só podem ser criadas em dias úteis',
                'errors' => ['creation_time' => ['📅 Tarefas só podem ser criadas em dias úteis']]
            ]);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addWarningMessage')
            ->with('⚠️ Tarefas só podem ser criadas em dias úteis');

        $result = $this->controller->createAction();

        $this->assertInstanceOf(ViewModel::class, $result);
    }

    /**
     * Testa visualização de tarefa existente
     */
    public function testViewActionExistingTask(): void
    {
        $taskId = 1;
        $task = new Task();
        $task->setId($taskId);
        $task->setTitle('Tarefa de teste');
        $task->setUserId(1);

        $this->taskService
            ->expects($this->once())
            ->method('getTaskById')
            ->with($taskId, 1)
            ->willReturn($task);

        $result = $this->controller->viewAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($task, $result->getVariable('task'));
    }

    /**
     * Testa visualização de tarefa inexistente
     */
    public function testViewActionNonExistentTask(): void
    {
        $taskId = 999;

        $this->taskService
            ->expects($this->once())
            ->method('getTaskById')
            ->with($taskId, 1)
            ->willReturn(null);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with('❌ Tarefa não encontrada');

        $this->redirect
            ->expects($this->once())
            ->method('toRoute')
            ->with('task-manager');

        $this->controller->viewAction();
    }

    /**
     * Testa edição de tarefa (GET)
     */
    public function testEditActionGet(): void
    {
        $taskId = 1;
        $task = new Task();
        $task->setId($taskId);
        $task->setTitle('Tarefa editável');
        $task->setStatus('pending');
        $task->setUserId(1);

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(false);

        $this->taskService
            ->expects($this->once())
            ->method('getTaskById')
            ->with($taskId, 1)
            ->willReturn($task);

        $result = $this->controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals($task, $result->getVariable('task'));
    }

    /**
     * Testa edição de tarefa não editável
     */
    public function testEditActionNonEditableTask(): void
    {
        $taskId = 1;
        $task = new Task();
        $task->setId($taskId);
        $task->setStatus('completed'); // Não pode ser editada
        $task->setUserId(1);

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(false);

        $this->taskService
            ->expects($this->once())
            ->method('getTaskById')
            ->with($taskId, 1)
            ->willReturn($task);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addWarningMessage')
            ->with('⚠️ Esta tarefa não pode ser editada');

        $this->redirect
            ->expects($this->once())
            ->method('toRoute')
            ->with('task-manager/view', ['id' => $taskId]);

        $this->controller->editAction();
    }

    /**
     * Testa atualização de tarefa válida (POST)
     */
    public function testEditActionPostValid(): void
    {
        $taskId = 1;
        $updateData = [
            'title' => 'Tarefa atualizada',
            'description' => 'Nova descrição',
            'priority' => 'urgent'
        ];

        $task = new Task();
        $task->setId($taskId);
        $task->setStatus('pending');
        $task->setUserId(1);

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('getPost')
            ->willReturn(new Parameters($updateData));

        $this->taskService
            ->expects($this->once())
            ->method('getTaskById')
            ->with($taskId, 1)
            ->willReturn($task);

        $this->taskService
            ->expects($this->once())
            ->method('updateTask')
            ->with($taskId, $updateData, 1)
            ->willReturn([
                'success' => true,
                'message' => 'Tarefa atualizada com sucesso'
            ]);

        $this->flashMessenger
            ->expects($this->once())
            ->method('addSuccessMessage')
            ->with('✅ Tarefa atualizada com sucesso!');

        $this->redirect
            ->expects($this->once())
            ->method('toRoute')
            ->with('task-manager/view', ['id' => $taskId]);

        $this->controller->editAction();
    }

    /**
     * Testa exclusão de tarefa via AJAX
     */
    public function testDeleteActionAjaxSuccess(): void
    {
        $taskId = 1;

        $this->request
            ->expects($this->once())
            ->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->taskService
            ->expects($this->once())
            ->method('deleteTask')
            ->with($taskId, 1)
            ->willReturn([
                'success' => true,
                'message' => 'Tarefa excluída com sucesso'
            ]);

        $this->response
            ->expects($this->once())
            ->method('setContent')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true && 
                       $data['message'] === 'Tarefa excluída com sucesso';
            }));

        $this->controller->deleteAction();
    }

    /**
     * Testa exclusão de tarefa com erro
     */
    public function testDeleteActionAjaxError(): void
    {
        $taskId = 1;

        $this->request
            ->expects($this->once())
            ->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->taskService
            ->expects($this->once())
            ->method('deleteTask')
            ->with($taskId, 1)
            ->willReturn([
                'success' => false,
                'message' => 'Esta tarefa não pode ser excluída'
            ]);

        $this->response
            ->expects($this->once())
            ->method('setContent')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === false && 
                       $data['message'] === 'Esta tarefa não pode ser excluída';
            }));

        $this->controller->deleteAction();
    }

    /**
     * Testa conclusão de tarefa via AJAX
     */
    public function testCompleteActionAjax(): void
    {
        $taskId = 1;

        $this->request
            ->expects($this->once())
            ->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->taskService
            ->expects($this->once())
            ->method('completeTask')
            ->with($taskId, 1)
            ->willReturn([
                'success' => true,
                'message' => 'Tarefa marcada como concluída'
            ]);

        $this->response
            ->expects($this->once())
            ->method('setContent')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true;
            }));

        $this->controller->completeAction();
    }

    /**
     * Testa duplicação de tarefa via AJAX
     */
    public function testDuplicateActionAjax(): void
    {
        $taskId = 1;

        $this->request
            ->expects($this->once())
            ->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->taskService
            ->expects($this->once())
            ->method('duplicateTask')
            ->with($taskId, 1)
            ->willReturn([
                'success' => true,
                'new_task_id' => 2,
                'message' => 'Tarefa duplicada com sucesso'
            ]);

        $this->response
            ->expects($this->once())
            ->method('setContent')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === true && 
                       $data['new_task_id'] === 2;
            }));

        $this->controller->duplicateAction();
    }

    /**
     * Testa acesso não autorizado
     */
    public function testUnauthorizedAccess(): void
    {
        $taskId = 1;
        
        $this->taskService
            ->expects($this->once())
            ->method('getTaskById')
            ->with($taskId, 1)
            ->willReturn(null); // Simula tarefa não encontrada ou sem permissão

        $this->flashMessenger
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with('❌ Tarefa não encontrada');

        $this->redirect
            ->expects($this->once())
            ->method('toRoute')
            ->with('task-manager');

        $this->controller->viewAction();
    }

    /**
     * Testa método não permitido para operações AJAX
     */
    public function testMethodNotAllowedAjax(): void
    {
        $this->request
            ->expects($this->once())
            ->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(false); // GET em vez de POST

        $this->response
            ->expects($this->once())
            ->method('setStatusCode')
            ->with(405);

        $this->response
            ->expects($this->once())
            ->method('setContent')
            ->with($this->callback(function ($content) {
                $data = json_decode($content, true);
                return $data['success'] === false && 
                       $data['message'] === 'Método não permitido';
            }));

        $this->controller->deleteAction();
    }

    /**
     * Testa busca AJAX de tarefas
     */
    public function testSearchActionAjax(): void
    {
        $searchTerm = 'importante';
        
        $this->request
            ->expects($this->once())
            ->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn(new Parameters(['q' => $searchTerm]));

        $expectedTasks = [
            ['id' => 1, 'title' => 'Tarefa importante'],
            ['id' => 2, 'title' => 'Outro item importante']
        ];

        $this->taskService
            ->expects($this->once())
            ->method('searchTasks')
            ->with($searchTerm, 1)
            ->willReturn($expectedTasks);

        $this->response
            ->expects($this->once())
            ->method('setContent')
            ->with($this->callback(function ($content) use ($expectedTasks) {
                $data = json_decode($content, true);
                return $data['success'] === true && 
                       $data['tasks'] === $expectedTasks;
            }));

        $this->controller->searchAction();
    }

    /**
     * Testa validação de entrada maliciosa
     */
    public function testMaliciousInputValidation(): void
    {
        $maliciousData = [
            'title' => '<script>alert("xss")</script>',
            'description' => '<img src="x" onerror="alert(1)">',
            'priority' => 'high\'; DROP TABLE tasks; --'
        ];

        $this->request
            ->expects($this->once())
            ->method('isPost')
            ->willReturn(true);

        $this->request
            ->expects($this->once())
            ->method('getPost')
            ->willReturn(new Parameters($maliciousData));

        // O service deve sanitizar/validar os dados
        $this->taskService
            ->expects($this->once())
            ->method('createTask')
            ->with($this->callback(function ($data) {
                // Verifica se dados maliciosos foram sanitizados
                return !str_contains($data['title'], '<script>') &&
                       !str_contains($data['description'], '<img');
            }))
            ->willReturn([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => ['title' => ['Caracteres inválidos detectados']]
            ]);

        $result = $this->controller->createAction();
        
        $this->assertInstanceOf(ViewModel::class, $result);
    }
}