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
 * ScriptoMediaResource.
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
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');
        $client = $services->get('Scripto\Mediawiki\ApiClient');

        $query = $request->getContent();
        $sItem = $em->find('Scripto\Entity\ScriptoItem', $query['scripto_item_id']);

        $medias = [];
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
        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');
        $client = $services->get('Scripto\Mediawiki\ApiClient');

        $this->validateResourceId($request->getId());
        list($projectId, $mediaId) = explode(':', $request->getId());

        // First, check if the Scripto media entity is already created. If not,
        // check if the given Omeka media belongs to an Omeka item that's
        // assigned to the given project.
        $query = $em->createQuery('
            SELECT m
            FROM Scripto\Entity\ScriptoMedia m
            JOIN m.scriptoItem i
            JOIN i.scriptoProject p
            WHERE m.media = :media_id
            AND p.id = :project_id'
        );
        $query->setParameters([
            'media_id' => $mediaId,
            'project_id' => $projectId,
        ]);
        try {
            // The Scripto media entity exists.
            $sMedia = $query->getSingleResult();
            $media = $sMedia->getMedia();
            $sItem = $sMedia->getScriptoItem();
        } catch (\Doctrine\ORM\NoResultException $e) {
            // The Scripto media entity does not exist.
            $media = $this->getAdapter('media')->findEntity($mediaId);
            $sItem = $this->getAdapter('scripto_items')->findEntity([
                'scriptoProject' => $projectId,
                'item' => $media->getItem()->getId(),
            ]);
            $sMedia = null;
        }
        return new Response(new ScriptoMediaResource($client, $sItem, $media, $sMedia));
    }

    public function validateResourceId($id)
    {
        if (!preg_match('/^\d+:\d$/', $id)) {
            throw new Exception\NotFoundException(sprintf(
                $this->getTranslator()->translate('Scripto\Entity\ScriptoMedia entity with ID %s not found'), $id
            ));
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        if (Request::CREATE === $request->getOperation()) {
            $data = $request->getContent();
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
}
