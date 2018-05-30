<?php
namespace Scripto\Controller\PublicApp;

use Scripto\Mediawiki\Exception\QueryException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class RevisionController extends AbstractActionController
{
    public function browseAction()
    {
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('scripto');
        }

        $response = $sMedia->pageRevisions(0, 100, $this->params()->fromQuery('continue'));
        $revisions = isset($response['query']['pages'][0]['revisions'])
            ? $response['query']['pages'][0]['revisions'] : [];
        $continue = isset($response['continue']) ? $response['continue']['rvcontinue'] : null;

        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $project);
        $view->setVariable('revisions', $revisions);
        $view->setVariable('continue', $continue);
        $this->layout()->setVariable('project', $project);
        $this->layout()->setVariable('sItem', $sItem);
        $this->layout()->setVariable('sMedia', $sMedia);
        return $view;
    }

    public function showAction()
    {
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('scripto');
        }

        try {
            $revision = $sMedia->pageRevision(0, $this->params('revision-id'));
        } catch (QueryException $e) {
            // Invalid revision ID
            return $this->redirect()->toRoute('admin/scripto');
        }

        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('revision', $revision);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $project);
        $this->layout()->setVariable('project', $project);
        $this->layout()->setVariable('sItem', $sItem);
        $this->layout()->setVariable('sMedia', $sMedia);
        return $view;
    }

    public function compareAction()
    {
        $sMedia = $this->scripto()->getRepresentation(
            $this->params('project-id'),
            $this->params('item-id'),
            $this->params('media-id')
        );
        if (!$sMedia) {
            return $this->redirect()->toRoute('admin/scripto');
        }

        $fromRevision = $sMedia->pageRevision(0, $this->params('from-revision-id'));
        $toRevision = $sMedia->pageRevision(0, $this->params('to-revision-id'));
        $compare = $this->scripto()->apiClient()->compareRevisions(
            $this->params('from-revision-id'),
            $this->params('to-revision-id')
        );

        $sItem = $sMedia->scriptoItem();
        $project = $sItem->scriptoProject();
        $view = new ViewModel;
        $view->setVariable('fromRevision', $fromRevision);
        $view->setVariable('toRevision', $toRevision);
        $view->setVariable('compare', $compare);
        $view->setVariable('sMedia', $sMedia);
        $view->setVariable('media', $sMedia->media());
        $view->setVariable('sItem', $sItem);
        $view->setVariable('item', $sItem->item());
        $view->setVariable('project', $project);
        $this->layout()->setVariable('project', $project);
        $this->layout()->setVariable('sItem', $sItem);
        $this->layout()->setVariable('sMedia', $sMedia);
        return $view;
    }
}
