<?php

namespace AccessControl\Acl;

use AccessControl\Mvc\Checker;
use Zend\Memory\Container\AccessController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

class Factory implements FactoryInterface, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    private $resources = null;

    /**
     * @var Acl;
     */
    private $acl;

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $sm
     *
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $sm)
    {
        $this->setServiceLocator($sm);
        /**
         * @var \Zend\Cache\Storage\Adapter\Filesystem $cache;
         */
        $cache = $sm->get('AccessControl\Acl\Cache');

        $rolesCombination = array();
        foreach($this->getRoles() as $role) {
            $rolesCombination[] = $role->getCode();
        }
        $rolesCombinationString = implode($rolesCombination, Checker::RESOURCE_PARTS_JOIN_BY);
        if($cache->hasItem($rolesCombinationString)) {
            $this->acl = $cache->getItem($rolesCombinationString);
        } else {
            $this->initAcl();
            $cache->addItem($rolesCombinationString, $this->acl);
            $cache->setTags($rolesCombinationString, $rolesCombination);
        }


        return $this->acl;
    }

    private function initAcl()
    {
        /**
         * @var \AccessControl\Entity\AclResource[] $resources
         */
        $resources = $this->getResources();
        $addedRoleCodes = array();
        $this->acl = new Acl();
        foreach ($this->getRoles() as $role) {
            $this->initRole($role, $resources);
            $addedRoleCodes[] = $role->getCode();
        }
        $this->acl->addRole(Checker::COMBINED_ROLE, $addedRoleCodes);

        foreach ($resources as $res) {
            if ($res->getIsPublic()) {
                $this->allowAccess(Checker::COMBINED_ROLE, $res, $resources);
            } else {
                $this->disallowAccess(Checker::COMBINED_ROLE, $res, $resources);
            }

            unset($resources[$res->getId()]);
        }
    }

    private function initRole(\AccessControl\Entity\AclRole $role, &$resources)
    {
        if ($this->acl->hasRole($role->getCode())) {
            return $this->acl;
        }
        if ($role->getParent() != null) {
            if (!$this->acl->hasRole($role->getParent()->getCode())) {
                $this->initRole($role->getParent(), $resources);
            }
            $this->acl->addRole($role->getCode(), $role->getParent()->getCode());
        } else {
            $this->acl->addRole($role->getCode());
        }
        /**
         * @var \AccessControl\Entity\AclResource $allowedResourceAction
         */
        foreach ($role->getResource() as $allowedResourceAction) {
            if (array_key_exists($allowedResourceAction->getId(), $resources)) {
                $this->allowAccess($role->getCode(), $allowedResourceAction, $resources);
                unset($resources[$allowedResourceAction->getId()]);
            }
        }

        return $this->acl;
    }

    private function allowAccess($role, \AccessControl\Entity\AclResource $resource, &$resources)
    {
        $resourceCode = $this->getResourceCode($resource);

        $this->addResource($resourceCode);
        $this->acl->allow($role, $resourceCode, $resource->getPrivilege());
        $sectionToAllow = '';
        foreach (explode(Checker::RESOURCE_PARTS_JOIN_BY, $resourceCode) as $sectionPart) {
            $sectionToAllow = trim($sectionToAllow . Checker::RESOURCE_PARTS_JOIN_BY . $sectionPart,
                Checker::RESOURCE_PARTS_JOIN_BY);
            $this->addResource($sectionToAllow);
            $this->acl->allow($role, $sectionToAllow, Checker::SECTION_PRIVILEGE);
        }
        foreach ($resource->getChildren() as $childResource) {
            if (array_key_exists($childResource->getId(), $resources)) {
                $this->allowAccess($role, $childResource, $resources);
            }
            unset($resources[$childResource->getId()]);
        }
    }

    private function disallowAccess($role, \AccessControl\Entity\AclResource $resource, &$resources)
    {
        $resourceCode = $this->getResourceCode($resource);

        $this->addResource($resourceCode);
        $this->acl->deny(Checker::COMBINED_ROLE, $resourceCode, $resource->getPrivilege());

        $sectionToAllow = '';
        foreach (explode(Checker::RESOURCE_PARTS_JOIN_BY, $resourceCode) as $sectionPart) {
            $sectionToAllow = trim($sectionToAllow . Checker::RESOURCE_PARTS_JOIN_BY . $sectionPart,
                Checker::RESOURCE_PARTS_JOIN_BY);
            $this->addResource($sectionToAllow);
            $this->acl->deny($role, $sectionToAllow, Checker::SECTION_PRIVILEGE);
        }
        foreach ($resource->getChildren() as $childResource) {
            if (array_key_exists($childResource->getId(), $resources)) {
                $this->disallowAccess($role, $childResource, $resources);
            }
            unset($resources[$childResource->getId()]);
        }
    }

    private function addResource($resourceCode)
    {
        if (!$this->acl->hasResource($resourceCode)) {
            $this->acl->addResource($resourceCode);
        }
    }

    private function getResourceCode(\AccessControl\Entity\AclResource $resource)
    {
        return $resource->getModule() . Checker::RESOURCE_PARTS_JOIN_BY . $resource->getResource();
    }

    private function getRoles()
    {
        return $this->getServiceLocator()->get('AccessControl\Roles');
    }

    private function getResources()
    {
        if ($this->resources == null) {
            /**
             * @var \AccessControl\Entity\AclResource[] $resources
             */
            $resources = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager')->getRepository('AccessControl\Entity\AclResource')->findAll();
            foreach ($resources as $res) {
                $this->resources[$res->getId()] = $res;
            }
        }

        return $this->resources;
    }
}
