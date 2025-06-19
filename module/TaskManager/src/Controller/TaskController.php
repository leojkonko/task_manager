<?php

namespace TaskManager\Controller;

use TaskManager\Model\TaskTable;
use TaskManager\Model\CategoryTable;
use TaskManager\Model\Task;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

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
        
        $tasks = $this->table->fetchAll($userId);
        $categories = $this->categoryTable->fetchAll($userId);
        
        return new ViewModel([
            'tasks' => $tasks,
            'categories' => $categories,
        ]);
    }

    public function addAction()
    {
        $userId = 1; // Usuário fixo por enquanto
        
        $form = new \TaskManager\Form\TaskForm();
        $form->get('submit')->setValue('Adicionar');

        // Buscar categorias para o select
        $categories = $this->categoryTable->getCategoriesForSelect($userId);
        $form->get('category_id')->setValueOptions(['' => 'Selecione uma categoria'] + $categories);

        $request = $this->getRequest();

        if (! $request->isPost()) {
            return ['form' => $form];
        }

        $task = new Task();
        $form->setData($request->getPost());

        if (! $form->isValid()) {
            return ['form' => $form];
        }

        $task->exchangeArray($form->getData());
        $task->user_id = $userId;
        $this->table->saveTask($task);
        
        $this->flashMessenger()->addSuccessMessage('Tarefa adicionada com sucesso!');
        return $this->redirect()->toRoute('task');
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (0 === $id) {
            return $this->redirect()->toRoute('task', ['action' => 'add']);
        }

        try {
            $task = $this->table->getTask($id);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Tarefa não encontrada.');
            return $this->redirect()->toRoute('task');
        }

        $userId = 1; // Usuário fixo por enquanto
        
        $form = new \TaskManager\Form\TaskForm();
        $form->bind($task);
        $form->get('submit')->setAttribute('value', 'Editar');

        // Buscar categorias para o select
        $categories = $this->categoryTable->getCategoriesForSelect($userId);
        $form->get('category_id')->setValueOptions(['' => 'Selecione uma categoria'] + $categories);

        $request = $this->getRequest();
        $viewData = ['id' => $id, 'form' => $form];

        if (! $request->isPost()) {
            return $viewData;
        }

        $form->setData($request->getPost());

        if (! $form->isValid()) {
            return $viewData;
        }

        try {
            $this->table->saveTask($task);
            $this->flashMessenger()->addSuccessMessage('Tarefa atualizada com sucesso!');
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Erro ao atualizar tarefa.');
        }

        return $this->redirect()->toRoute('task');
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
                $this->table->deleteTask($id);
                $this->flashMessenger()->addSuccessMessage('Tarefa excluída com sucesso!');
            }

            return $this->redirect()->toRoute('task');
        }

        return [
            'id'    => $id,
            'task' => $this->table->getTask($id),
        ];
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
            if ($task->status === 'completed') {
                $task->status = 'pending';
            } else {
                $task->status = 'completed';
            }
            
            $this->table->saveTask($task);
            $this->flashMessenger()->addSuccessMessage('Status da tarefa atualizado!');
            
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Erro ao atualizar status da tarefa.');
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
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Tarefa não encontrada.');
            return $this->redirect()->toRoute('task');
        }

        return new ViewModel([
            'task' => $task,
        ]);
    }
}