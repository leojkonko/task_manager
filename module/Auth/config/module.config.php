<?php

declare(strict_types=1);

use Auth\Controller\AuthController;
use Auth\Factory\AuthControllerFactory;
use Auth\Factory\AuthServiceFactory;
use Auth\Factory\AuthenticationManagerFactory;
use Auth\Service\AuthService;
use Auth\Service\AuthenticationManager;
use Auth\View\Helper\Identity;
use Auth\View\Helper\IdentityFactory;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'auth' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/auth',
                    'defaults' => [
                        'controller' => AuthController::class,
                        'action' => 'login',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'login' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/login',
                            'defaults' => [
                                'action' => 'login',
                            ],
                        ],
                    ],
                    'register' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/register',
                            'defaults' => [
                                'action' => 'register',
                            ],
                        ],
                    ],
                    'logout' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/logout',
                            'defaults' => [
                                'action' => 'logout',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AuthController::class => AuthControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            AuthService::class => AuthServiceFactory::class,
            AuthenticationManager::class => AuthenticationManagerFactory::class,
        ],
    ],
    'view_helpers' => [
        'aliases' => [
            'identity' => Identity::class,
        ],
        'factories' => [
            Identity::class => IdentityFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
