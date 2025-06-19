<?php

namespace TaskManager\Form;

use Laminas\Form\Form;

class CategoryForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('category');

        $this->add([
            'name' => 'id',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Nome',
            ],
            'attributes' => [
                'required' => true,
                'size' => 40,
                'maxlength' => 50,
                'class' => 'form-control',
                'placeholder' => 'Digite o nome da categoria'
            ],
        ]);

        $this->add([
            'name' => 'color',
            'type' => 'color',
            'options' => [
                'label' => 'Cor',
            ],
            'attributes' => [
                'class' => 'form-control',
                'value' => '#007bff',
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
                'rows' => 3,
                'placeholder' => 'Descreva o propósito desta categoria'
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