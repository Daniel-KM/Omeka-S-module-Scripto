<?php declare(strict_types=1);
namespace Scripto\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Scripto\Controller\Admin\ProjectController;

class ProjectControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ProjectController($services->get('Omeka\HtmlPurifier'));
    }
}
