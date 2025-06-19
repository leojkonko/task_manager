<?php

namespace TaskManager;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return [
            'factories' => [
                Model\TaskTable::class => function($container) {
                    $tableGateway = $container->get(Model\TaskTableGateway::class);
                    return new Model\TaskTable($tableGateway);
                },
                Model\TaskTableGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Task());
                    return new TableGateway('tasks', $dbAdapter, null, $resultSetPrototype);
                },
                Model\CategoryTable::class => function($container) {
                    $tableGateway = $container->get(Model\CategoryTableGateway::class);
                    return new Model\CategoryTable($tableGateway);
                },
                Model\CategoryTableGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\Category());
                    return new TableGateway('task_categories', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserTable::class => function($container) {
                    $tableGateway = $container->get(Model\UserTableGateway::class);
                    return new Model\UserTable($tableGateway);
                },
                Model\UserTableGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new Model\User());
                    return new TableGateway('users', $dbAdapter, null, $resultSetPrototype);
                },
            ],
        ];
    }

    public function getControllerConfig()
    {
        return [
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
        ];
    }
}