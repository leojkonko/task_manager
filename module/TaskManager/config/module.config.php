<?php

declare(strict_types=1);

namespace TaskManager;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\ResultSet\ResultSet;
use ArrayObject;

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
            'api' => [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/api',
                    'defaults' => [
                        'controller' => Controller\ApiController::class,
                    ],
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'tasks' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/tasks',
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'create' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/create',
                                    'defaults' => [
                                        'action' => 'create',
                                    ],
                                ],
                            ],
                            'list' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/list',
                                    'defaults' => [
                                        'action' => 'list',
                                    ],
                                ],
                            ],
                            'update' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/update/[:id]',
                                    'constraints' => [
                                        'id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'update',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/delete/[:id]',
                                    'constraints' => [
                                        'id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'delete',
                                    ],
                                ],
                            ],
                            'get' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/[:id]',
                                    'constraints' => [
                                        'id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'get',
                                    ],
                                ],
                            ],
                            'complete' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/complete/[:id]',
                                    'constraints' => [
                                        'id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'complete',
                                    ],
                                ],
                            ],
                            'start' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/start/[:id]',
                                    'constraints' => [
                                        'id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'start',
                                    ],
                                ],
                            ],
                            'duplicate' => [
                                'type' => Segment::class,
                                'options' => [
                                    'route' => '/duplicate/[:id]',
                                    'constraints' => [
                                        'id' => '[0-9]+',
                                    ],
                                    'defaults' => [
                                        'action' => 'duplicate',
                                    ],
                                ],
                            ],
                            'statistics' => [
                                'type' => Literal::class,
                                'options' => [
                                    'route' => '/statistics',
                                    'defaults' => [
                                        'action' => 'statistics',
                                    ],
                                ],
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
                    $container->get(Service\TaskService::class),
                    $container->get(\Auth\Service\AuthenticationManager::class)
                );
            },
            Controller\ApiController::class => function ($container) {
                return new Controller\ApiController(
                    $container->get(Service\TaskService::class)
                );
            },
        ],
    ],

    'service_manager' => [
        'factories' => [
            Service\TaskService::class => function ($container) {
                $taskRepository = $container->get(Repository\TaskRepository::class);
                return new Service\TaskService($taskRepository);
            },
            Service\EmailNotificationService::class => Factory\EmailNotificationServiceFactory::class,
            Repository\TaskRepository::class => function ($container) {
                $taskTable = $container->get(Model\TaskTable::class);
                return new Repository\TaskRepository($taskTable);
            },
            Model\TaskTable::class => function ($container) {
                $tableGateway = $container->get(Model\TaskTableGateway::class);
                return new Model\TaskTable($tableGateway);
            },
            Model\TaskTableGateway::class => function ($container) {
                $dbAdapter = $container->get(AdapterInterface::class);
                $resultSetPrototype = new ResultSet();
                $resultSetPrototype->setArrayObjectPrototype(new ArrayObject());
                return new TableGateway('tasks', $dbAdapter, null, $resultSetPrototype);
            },
            Middleware\TaskValidationMiddleware::class => function ($container) {
                return new Middleware\TaskValidationMiddleware();
            },
            Exception\ValidationExceptionHandler::class => function ($container) {
                return new Exception\ValidationExceptionHandler();
            },
            Helper\MessageHelper::class => function ($container) {
                return new Helper\MessageHelper();
            },
        ],
    ],

    'view_helpers' => [
        'aliases' => [
            'getStatusBadgeClass' => View\Helper\GetStatusBadgeClass::class,
            'getPriorityBadgeClass' => View\Helper\GetPriorityBadgeClass::class,
        ],
        'factories' => [
            View\Helper\GetStatusBadgeClass::class => function () {
                return new View\Helper\GetStatusBadgeClass();
            },
            View\Helper\GetPriorityBadgeClass::class => function () {
                return new View\Helper\GetPriorityBadgeClass();
            },
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'task-manager' => __DIR__ . '/../view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
        'template_map' => [
            // Mapear templates para evitar erro de resolução
        ],
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
    ],
];
