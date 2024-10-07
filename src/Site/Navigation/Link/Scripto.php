<?php declare(strict_types=1);
namespace Scripto\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\LinkInterface;
use Omeka\Stdlib\ErrorStore;

class Scripto implements LinkInterface
{
    public function getName()
    {
        return 'Scripto'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/scripto';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['project_id']) || '' === trim($data['project_id'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: Scripto link missing project');
            return false;
        }
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        if (isset($data['label']) && '' !== trim($data['label'])) {
            return $data['label'];
        }

        $services = $site->getServiceLocator();
        if (empty($data['project_id']) || !is_numeric($data['project_id'])) {
            $translator = $services->get('MvcTranslator');
            return $translator->translate('[Missing project]'); // @translate
        }

        try {
            /** @var \Scripto\Api\Representation\ScriptoProjectRepresentation $project */
            $project = $services->get('Omeka\ApiManager')->read('scripto_projects', ['id' => $data['project_id']])->getContent();
            return $project->title();
        } catch (\Exception $e) {
            $translator = $services->get('MvcTranslator');
            return $translator->translate('[Missing project]'); // @translate
        }
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        return [
            'route' => 'scripto-project-id',
            'params' => [
                'site-slug' => $site->slug(),
                'site-project-id' => $data['project_id'],
                'project-id' => $data['project_id'],
            ],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
            'project_id' => $data['project_id'],
        ];
    }
}
