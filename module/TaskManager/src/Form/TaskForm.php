<?php

namespace TaskManager\Form;

use Laminas\Form\Form;

class TaskForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('task');

        $this->add([
            'name' => 'id',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'title',
            'type' => 'text',
            'options' => [
                'label' => 'Título',
            ],
            'attributes' => [
                'required' => true,
                'size' => 40,
                'maxlength' => 200,
                'class' => 'form-control',
                'placeholder' => 'Digite o título da tarefa'
            ],
        ]);

        $this->add([
            'name' => 'description',
            'type' => 'textarea',
            'options' => [
                'label' => 'Descrição',
            ],
            'attributes' => [
                'class' => 'form-control',
                'rows' => 4,
                'placeholder' => 'Descreva os detalhes da tarefa'
            ],
        ]);

        $this->add([
            'name' => 'status',
            'type' => 'select',
            'options' => [
                'label' => 'Status',
                'value_options' => [
                    'pending' => 'Pendente',
                    'in_progress' => 'Em Andamento',
                    'completed' => 'Concluída',
                    'cancelled' => 'Cancelada',
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'priority',
            'type' => 'select',
            'options' => [
                'label' => 'Prioridade',
                'value_options' => [
                    'low' => 'Baixa',
                    'medium' => 'Média',
                    'high' => 'Alta',
                    'urgent' => 'Urgente',
                ],
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'due_date',
            'type' => 'datetime-local',
            'options' => [
                'label' => 'Data de Vencimento',
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'category_id',
            'type' => 'select',
            'options' => [
                'label' => 'Categoria',
                'value_options' => [], // Será preenchido no controller
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Salvar',
                'id'    => 'submitbutton',
                'class' => 'btn btn-primary',
            ],
        ]);
    }
}