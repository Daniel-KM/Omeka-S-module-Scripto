<?php
namespace Scripto\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Scripto\Api\ScriptoMediaResource;
use Scripto\Entity\ScriptoMedia;

/**
 * Scripto media adapter
 *
 * Must override SCRUD operations because of the unconventional construction of
 * ScriptoMediaResource and because the corresponding MediaWiki pages must be
 * edited before flushing the entity manager during create/update.
 */
class ScriptoMediaAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'scripto_media';
    }

    public function getRepresentationClass()
    {
        return 'Scripto\Api\Representation\ScriptoMediaRepresentation';
    }

    public function getEntityClass()
    {
        return 'Scripto\Entity\ScriptoMedia';
    }

    public function search(Request $request)
    {
        $sItemId = $request->getValue('scripto_item_id');
        if (!$sItemId) {
            throw new Exception\BadRequestException('The search query must include scripto_item_id'); // @translate
        }
        $sItem = $this->getAdapter('scripto_items')->findEntity($sItemId);

        $medias = [];
        $em = $this->getEntityManager();
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        foreach ($sItem->getItem()->getMedia() as $media) {
            $sMedia = $em->getRepository('Scripto\Entity\ScriptoMedia')->findOneBy([
                'scriptoItem' => $sItem->getId(),
                'media' => $media->getId(),
            ]);
            $medias[] = new ScriptoMediaResource($client, $sItem, $media, $sMedia);
        }
        return new Response($medias);
    }

    public function create(Request $request)
    {
        $sMedia = new ScriptoMedia;
        $this->hydrateEntity($request, $sMedia, new ErrorStore);
        $this->editMediawikiPage($sMedia, $request->getValue('o-module-scripto:text'));
        $this->getEntityManager()->persist($sMedia);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->refresh($sMedia);

        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return new Response(new ScriptoMediaResource(
            $client, $sMedia->getScriptoItem(), $sMedia->getMedia(), $sMedia
        ));
    }

    public function read(Request $request)
    {
        if (!$this->scriptoMediaResourceIdIsValid($request->getId())) {
            throw new Exception\BadRequestException('Invalid resource ID format; must use "scripto_project_id:media_id"'); // @translate
        }
        list($projectId, $mediaId) = explode(':', $request->getId());

        $query = $this->getEntityManager()->createQuery('
            SELECT m
            FROM Scripto\Entity\ScriptoMedia m
            JOIN m.scriptoItem i
            JOIN i.scriptoProject p
            WHERE m.media = :media_id
            AND p.id = :project_id'
        )->setParameters([
            'media_id' => $mediaId,
            'project_id' => $projectId,
        ]);
        try {
            // First, check if the Scripto media entity is already created.
            $sMedia = $query->getSingleResult();
            $media = $sMedia->getMedia();
            $sItem = $sMedia->getScriptoItem();
        } catch (\Doctrine\ORM\NoResultException $e) {
            // If not, check if the given Omeka media belongs to an Omeka item
            // that's assigned to the given project.
            $media = $this->getAdapter('media')->findEntity($mediaId);
            $sItem = $this->getAdapter('scripto_items')->findEntity([
                'scriptoProject' => $projectId,
                'item' => $media->getItem()->getId(),
            ]);
            $sMedia = null;
        }

        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        return new Response(new ScriptoMediaResource($client, $sItem, $media, $sMedia));
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        if (Request::CREATE === $request->getOperation()) {
            $data = $request->getContent();
            if (!isset($data['o-module-scripto:text'])) {
                $errorStore->addError('o:media', 'A Scripto media must have text on creation.'); // @translate
            }
            if (!isset($data['o-module-scripto:item']['o:id'])) {
                $errorStore->addError('o-module-scripto:item', 'A Scripto media must be assigned a Scripto item on creation.'); // @translate
            }
            if (!isset($data['o:media']['o:id'])) {
                $errorStore->addError('o:media', 'A Scripto media must be assigned an Omeka media on creation.'); // @translate
            }
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        if (Request::CREATE === $request->getOperation()) {
            $data = $request->getContent();

            $sItem = $this->getAdapter('scripto_items')->findEntity($data['o-module-scripto:item']['o:id']);
            $media = $this->getAdapter('media')->findEntity($data['o:media']['o:id']);

            $entity->setScriptoItem($sItem);
            $entity->setMedia($media);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (null === $entity->getScriptoItem()) {
            $errorStore->addError('o-module-scripto:item', 'A Scripto item must not be null'); // @translate
        }
        if (null === $entity->getMedia()) {
            $errorStore->addError('o:media', 'A media must not be null'); // @translate
        }
    }

    /**
     * Is the passed Scripto media resource ID valid?
     *
     * @param int $id
     * @return bool
     */
    public function scriptoMediaResourceIdIsValid($id)
    {
        return preg_match('/^\d+:\d$/', $id); // scripto_project_id:media_id
    }

    /**
     * Get the Scripto media resource ID from the Scripto media entity.
     *
     * @param ScriptoMedia $sMedia
     * @return string
     */
    public function getScriptoMediaResourceId(ScriptoMedia $sMedia)
    {
        return sprintf(
            '%s:%s',
            $sMedia->getScriptoItem()->getScriptoProject()->getId(),
            $sMedia->getMedia()->getId()
        );
    }

    /**
     * Edit a corresponding MediaWiki page.
     *
     * @param ScriptoMedia $sMedia
     * @param string $text
     */
    public function editMediawikiPage(ScriptoMedia $sMedia, $text)
    {
        $client = $this->getServiceLocator()->get('Scripto\Mediawiki\ApiClient');
        $pageTitle = $this->getScriptoMediaResourceId($sMedia);
        $page = $client->queryPage($pageTitle);
        $pageIsCreated = $client->pageIsCreated($page);
        if (!$pageIsCreated && !$client->userCan($page, 'createpage')) {
            throw new Exception\RuntimeException(sprintf(
                $this->getTranslator()->translate('The MediaWiki user does not have the necessary permissions to create the page "%s"'),
                $pageTitle
            ));
        }
        if ($pageIsCreated && !$client->userCan($page, 'edit')) {
            throw new Exception\RuntimeException(sprintf(
                $this->getTranslator()->translate('The MediaWiki user does not have the necessary permissions to edit the page "%s"'),
                $pageTitle
            ));
        }
        $client->editPage($pageTitle, $text);
    }
}
