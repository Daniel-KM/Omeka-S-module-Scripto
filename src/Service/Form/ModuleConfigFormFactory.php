<?php declare(strict_types=1);
namespace Scripto\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Scripto\Form\ModuleConfigForm;

class ModuleConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ModuleConfigForm;
        $form->setApiClientDependencies(
            $services->get('Omeka\HttpClient'),
            $services->get('Omeka\Settings')->get('time_zone', 'UTC')
        );
        return $form;
    }
}
