<?php

namespace TaskManager\Controller;

use TaskManager\Model\CategoryTable;
use TaskManager\Model\Category;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class CategoryController extends AbstractActionController
{
    private $table;

    public function __construct(CategoryTable $table)
    {
        $this->table = $table;
    }

    public function indexAction()
    {
        // Por enquanto, vamos usar user_id = 1 (admin) até implementarmos autenticação
        $userId = 1;

        $categories = $this->table->fetchAll($userId);

        return new ViewModel([
            'categories' => $categories,
        ]);
    }

    public function addAction()
    {
        $userId = 1; // Usuário fixo por enquanto

        $form = new \TaskManager\Form\CategoryForm();
        $form->get('submit')->setValue('Adicionar');

        $request = $this->getRequest();

        if (! $request->isPost()) {
            return ['form' => $form];
        }

        $category = new Category();
        $form->setData($request->getPost());

        if (! $form->isValid()) {
            return ['form' => $form];
        }

        $category->exchangeArray($form->getData());
        $category->user_id = $userId;
        $this->table->saveCategory($category);

        $this->flashMessenger()->addSuccessMessage('Categoria adicionada com sucesso!');
        return $this->redirect()->toRoute('category');
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (0 === $id) {
            return $this->redirect()->toRoute('category', ['action' => 'add']);
        }

        try {
            $category = $this->table->getCategory($id);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Categoria não encontrada.');
            return $this->redirect()->toRoute('category');
        }

        $form = new \TaskManager\Form\CategoryForm();
        $form->bind($category);
        $form->get('submit')->setAttribute('value', 'Editar');

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
            $this->table->saveCategory($category);
            $this->flashMessenger()->addSuccessMessage('Categoria atualizada com sucesso!');
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Erro ao atualizar categoria.');
        }

        return $this->redirect()->toRoute('category');
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('category');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'Não');

            if ($del == 'Sim') {
                $id = (int) $request->getPost('id');
                $this->table->deleteCategory($id);
                $this->flashMessenger()->addSuccessMessage('Categoria excluída com sucesso!');
            }

            return $this->redirect()->toRoute('category');
        }

        return [
            'id'       => $id,
            'category' => $this->table->getCategory($id),
        ];
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);

        if (0 === $id) {
            return $this->redirect()->toRoute('category');
        }

        try {
            $category = $this->table->getCategory($id);
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Categoria não encontrada.');
            return $this->redirect()->toRoute('category');
        }

        return new ViewModel([
            'category' => $category,
        ]);
    }
}
