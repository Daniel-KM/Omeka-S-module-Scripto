<?php declare(strict_types=1);
namespace Scripto\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Scripto\ControllerPlugin\Scripto;

class ScriptoFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Scripto($services->get('Scripto\Mediawiki\ApiClient'));
    }
}
