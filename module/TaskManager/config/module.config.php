<?php

namespace TaskManager;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'task' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/task[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\TaskController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'category' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/category[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\CategoryController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\TaskController::class => function($container) {
                return new Controller\TaskController(
                    $container->get(Model\TaskTable::class),
                    $container->get(Model\CategoryTable::class)
                );
            },
            Controller\CategoryController::class => function($container) {
                return new Controller\CategoryController(
                    $container->get(Model\CategoryTable::class)
                );
            },
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'task-manager' => __DIR__ . '/../view',
        ],
    ],
];