<?php

declare(strict_types=1);

namespace Auth\View\Helper;

use Auth\Service\AuthenticationManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class IdentityFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Identity
    {
        $authManager = $container->get(AuthenticationManager::class);
        return new Identity($authManager);
    }
}
