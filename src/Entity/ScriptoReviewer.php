<?php declare(strict_types=1);
namespace Scripto\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\User;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"user_id", "scripto_project_id"}
 *         )
 *     }
 * )
 */
class ScriptoReviewer extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\User"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="CASCADE"
     * )
     */
    protected $user;

    /**
     * @ManyToOne(
     *     targetEntity="ScriptoProject",
     *     inversedBy="reviewers"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $scriptoProject;

    public function getId()
    {
        return $this->id;
    }

    public function setUser(User $user = null): void
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setScriptoProject(ScriptoProject $scriptoProject): void
    {
        $this->scriptoProject = $scriptoProject;
    }

    public function getScriptoProject()
    {
        return $this->scriptoProject;
    }

    public function setCreated(DateTime $dateTime): void
    {
        $this->created = $dateTime;
    }

    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $this->setCreated(new DateTime('now'));
    }
}
