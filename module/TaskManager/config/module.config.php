<?php

declare(strict_types=1);

namespace TaskManager;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\Db\Adapter\AdapterInterface;

return [
    'router' => [
        'routes' => [
            'task-manager' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/tasks',
                    'defaults' => [
                        'controller' => Controller\TaskController::class,
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/view/[:id]',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'create' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/create',
                            'defaults' => [
                                'action' => 'create',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/edit/[:id]',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'edit',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/delete/[:id]',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'delete',
                            ],
                        ],
                    ],
                    'complete' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/complete/[:id]',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'complete',
                            ],
                        ],
                    ],
                    'start' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/start/[:id]',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'start',
                            ],
                        ],
                    ],
                    'duplicate' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'    => '/duplicate/[:id]',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'duplicate',
                            ],
                        ],
                    ],
                    'statistics' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/statistics',
                            'defaults' => [
                                'action' => 'statistics',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\TaskController::class => function ($container) {
                return new Controller\TaskController(
                    $container->get(Service\TaskService::class)
                );
            },
        ],
    ],

    'service_manager' => [
        'factories' => [
            Service\TaskService::class => function ($container) {
                return new Service\TaskService(
                    $container->get(Repository\TaskRepository::class)
                );
            },
            Repository\TaskRepository::class => function ($container) {
                return new Repository\TaskRepository(
                    $container->get(AdapterInterface::class)
                );
            },
        ],
    ],

    'view_helpers' => [
        'aliases' => [
            'getStatusBadgeClass' => View\Helper\GetStatusBadgeClass::class,
            'getPriorityBadgeClass' => View\Helper\GetPriorityBadgeClass::class,
        ],
        'factories' => [
            View\Helper\GetStatusBadgeClass::class => function() {
                return new View\Helper\GetStatusBadgeClass();
            },
            View\Helper\GetPriorityBadgeClass::class => function() {
                return new View\Helper\GetPriorityBadgeClass();
            },
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'task-manager' => __DIR__ . '/../view',
        ],
    ],
];
