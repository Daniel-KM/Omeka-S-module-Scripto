<?php declare(strict_types=1);
namespace Scripto\PermissionsAssertion;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Scripto\Entity\ScriptoItem;
use Scripto\Entity\ScriptoMedia;
use Scripto\Entity\ScriptoProject;

class UserOwnsProjectAssertion implements AssertionInterface
{
    public function assert(Acl $acl, RoleInterface $role = null,
        ResourceInterface $resource = null, $privilege = null
    ) {
        if ($resource instanceof ScriptoProject) {
            $project = $resource;
        } elseif ($resource instanceof ScriptoItem) {
            $project = $resource->getScriptoProject();
        } elseif ($resource instanceof ScriptoMedia) {
            $project = $resource->getScriptoItem()->getScriptoProject();
        } else {
            return false;
        }
        return $role && $role === $project->getOwner();
    }
}
