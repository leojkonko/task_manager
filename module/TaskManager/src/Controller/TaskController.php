<?php

namespace TaskManager\Controller;

use TaskManager\Model\TaskTable;
use TaskManager\Model\CategoryTable;
use TaskManager\Model\Task;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Exception;
use InvalidArgumentException;
use DomainException;

class TaskController extends AbstractActionController
{
    private $table;
    private $categoryTable;

    public function __construct(TaskTable $table, CategoryTable $categoryTable)
    {
        $this->table = $table;
        $this->categoryTable = $categoryTable;
    }

    public function indexAction()
    {
        // Por enquanto, vamos usar user_id = 1 (admin) até implementarmos autenticação
        $userId = 1;
        
        try {
            $tasks = $this->table->fetchAll($userId);
            $categories = $this->categoryTable->fetchAll($userId);
            
            return new ViewModel([
                'tasks' => $tasks,
                'categories' => $categories,
            ]);
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage('Erro ao carregar tarefas: ' . $e->getMessage());
            return new ViewModel([
                'tasks' => [],
                'categories' => [],
            ]);
        }
    }

    public function addAction()
    {
        $userId = 1; // Usuário fixo por enquanto
        
        $form = new \TaskManager\Form\TaskForm();
        $form->get('submit')->setValue('Adicionar');

        try {
            // Buscar categorias para o select
            $categories = $this->categoryTable->getCategoriesForSelect($userId);
            $form->get('category_id')->setValueOptions(['' => 'Selecione uma categoria'] + $categories);
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage('Erro ao carregar categorias.');
            $categories = [];
            $form->get('category_id')->setValueOptions(['' => 'Selecione uma categoria']);
        }

        $request = $this->getRequest();

        if (!$request->isPost()) {
            return ['form' => $form];
        }

        $task = new Task();
        $form->setData($request->getPost());

        if (!$form->isValid()) {
            return ['form' => $form];
        }

        try {
            // Validar dados do formulário
            $formData = $form->getData();
            
            // Aplicar validações do servidor
            $this->validateTaskData($formData);
            
            $task->exchangeArray($formData);
            $task->user_id = $userId;
            
            // Validar o modelo
            $task->validate();
            
            $this->table->saveTask($task);
            
            $this->flashMessenger()->addSuccessMessage('Tarefa adicionada com sucesso!');
            return $this->redirect()->toRoute('task');
            
        } catch (InvalidArgumentException $e) {
            $this->flashMessenger()->addErrorMessage('Dados inválidos: ' . $e->getMessage());
        } catch (DomainException $e) {
            $this->flashMessenger()->addErrorMessage('Erro de validação: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage('Erro ao salvar tarefa: ' . $e->getMessage());
        }
        
        return ['form' => $form];
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (0 === $id) {
            return $this->redirect()->toRoute('task', ['action' => 'add']);
        }

        try {
            $task = $this->table->getTask($id);
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage('Tarefa não encontrada.');
            return $this->redirect()->toRoute('task');
        }

        $userId = 1; // Usuário fixo por enquanto
        
        $form = new \TaskManager\Form\TaskForm();
        $form->bind($task);
        $form->get('submit')->setAttribute('value', 'Editar');

        try {
            // Buscar categorias para o select
            $categories = $this->categoryTable->getCategoriesForSelect($userId);
            $form->get('category_id')->setValueOptions(['' => 'Selecione uma categoria'] + $categories);
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage('Erro ao carregar categorias.');
        }

        $request = $this->getRequest();
        $viewData = ['id' => $id, 'form' => $form];

        if (!$request->isPost()) {
            return $viewData;
        }

        $form->setData($request->getPost());

        if (!$form->isValid()) {
            return $viewData;
        }

        try {
            // Validar dados do formulário
            $formData = $form->getData();
            
            // Aplicar validações do servidor
            $this->validateTaskData($formData);
            
            // Validar o modelo
            $task->validate();
            
            $this->table->saveTask($task);
            $this->flashMessenger()->addSuccessMessage('Tarefa atualizada com sucesso!');
            
        } catch (InvalidArgumentException $e) {
            $this->flashMessenger()->addErrorMessage('Dados inválidos: ' . $e->getMessage());
            return $viewData;
        } catch (DomainException $e) {
            $this->flashMessenger()->addErrorMessage('Erro de validação: ' . $e->getMessage());
            return $viewData;
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage('Erro ao atualizar tarefa: ' . $e->getMessage());
            return $viewData;
        }

        return $this->redirect()->toRoute('task');
    }

    /**
     * Validar dados da tarefa no servidor
     */
    private function validateTaskData(array $data)
    {
        // Validar título obrigatório
        if (empty(trim($data['title'] ?? ''))) {
            throw new InvalidArgumentException('O título da tarefa é obrigatório.');
        }
        
        // Validar comprimento do título
        $title = trim($data['title']);
        if (strlen($title) < 3) {
            throw new InvalidArgumentException('O título deve ter pelo menos 3 caracteres.');
        }
        
        if (strlen($title) > 200) {
            throw new InvalidArgumentException('O título não pode ter mais de 200 caracteres.');
        }
        
        // Validar status se fornecido
        if (!empty($data['status']) && !in_array($data['status'], Task::getValidStatuses())) {
            throw new InvalidArgumentException('Status inválido.');
        }
        
        // Validar prioridade se fornecida
        if (!empty($data['priority']) && !in_array($data['priority'], Task::getValidPriorities())) {
            throw new InvalidArgumentException('Prioridade inválida.');
        }
        
        // Validar data de vencimento se fornecida
        if (!empty($data['due_date'])) {
            $dueDate = \DateTime::createFromFormat('Y-m-d\TH:i', $data['due_date']);
            if (!$dueDate) {
                $dueDate = \DateTime::createFromFormat('Y-m-d H:i:s', $data['due_date']);
            }
            if (!$dueDate) {
                throw new InvalidArgumentException('Formato de data de vencimento inválido.');
            }
        }
        
        // Validar categoria se fornecida
        if (!empty($data['category_id'])) {
            $categoryId = (int)$data['category_id'];
            try {
                $this->categoryTable->getCategory($categoryId);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Categoria selecionada não existe.');
            }
        }
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('task');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'Não');

            if ($del == 'Sim') {
                $id = (int) $request->getPost('id');
                try {
                    $this->table->deleteTask($id);
                    $this->flashMessenger()->addSuccessMessage('Tarefa excluída com sucesso!');
                } catch (Exception $e) {
                    $this->flashMessenger()->addErrorMessage('Erro ao excluir tarefa: ' . $e->getMessage());
                }
            }

            return $this->redirect()->toRoute('task');
        }

        try {
            $task = $this->table->getTask($id);
            return [
                'id' => $id,
                'task' => $task,
            ];
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage('Tarefa não encontrada.');
            return $this->redirect()->toRoute('task');
        }
    }

    public function toggleStatusAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (!$id) {
            return $this->redirect()->toRoute('task');
        }

        try {
            $task = $this->table->getTask($id);
            
            // Alternar status entre pending/in_progress e completed
            if ($task->status === Task::STATUS_COMPLETED) {
                $task->markAsPending();
            } else {
                $task->markAsCompleted();
            }
            
            $this->table->saveTask($task);
            $this->flashMessenger()->addSuccessMessage('Status da tarefa atualizado!');
            
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage('Erro ao atualizar status da tarefa: ' . $e->getMessage());
        }

        return $this->redirect()->toRoute('task');
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        
        if (0 === $id) {
            return $this->redirect()->toRoute('task');
        }

        try {
            $task = $this->table->getTask($id);
            return new ViewModel([
                'task' => $task,
            ]);
        } catch (Exception $e) {
            $this->flashMessenger()->addErrorMessage('Tarefa não encontrada.');
            return $this->redirect()->toRoute('task');
        }
    }
}