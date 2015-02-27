<?php

namespace AccessControl\Acl;

use AccessControl\Entity\AclResource as AclResourceEntity;
use AccessControl\Entity\AclRole as AclRoleEntity;
use AccessControl\Exception;
use AccessControl\Mvc\Checker;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class Factory
 *
 * @package AccessControl\Acl
 * @author  Pavel Matviienko
 */
class Factory implements FactoryInterface, ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var AclResourceEntity[]
     */
    private $resources = null;

    /**
     * @var Acl;
     */
    private $acl;

    /**
     * Create service.
     *
     * @param ServiceLocatorInterface $sm
     *
     * @return Acl
     * @throws Exception\CacheNotConfiguredException
     */
    public function createService(ServiceLocatorInterface $sm)
    {
        $this->setServiceLocator($sm);
        $config = $sm->get('config');
        if (!empty($config['access_control']['enabled'])) {
            if(!$this->getServiceLocator()->has('AccessControl/Acl/Cache')){
                throw new Exception\CacheNotConfiguredException('TO use acl, you should no configure cache and set it to service manager with key "AccessControl/Acl/Cache"');
            }
            /**
             * @var \Zend\Cache\Storage\Adapter\Filesystem $cache ;
             */
            $cache = $sm->get('AccessControl/Acl/Cache');

            $rolesCombination = array();
            foreach ($this->getRoles() as $role) {
                $rolesCombination[] = $role->getCode();
            }
            $rolesCombinationString = implode($rolesCombination, Checker::RESOURCE_PARTS_JOIN_BY);
            if ($cache->hasItem($rolesCombinationString)) {
                $this->acl = $cache->getItem($rolesCombinationString);
            } else {
                $this->initAcl();
                $cache->addItem($rolesCombinationString, $this->acl);
                $cache->setTags($rolesCombinationString, $rolesCombination);
                $this->acl->setEnabled(true);
            }
        } else {
            $this->acl = new Acl();
            $this->acl->setEnabled(false);
        }

        return $this->acl;
    }

    /**
     * Initializing Acl.
     */
    private function initAcl()
    {
        /**
         * @var AclResourceEntity[] $resources
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

    /**
     * Initializing Acl Role.
     *
     * @param AclRoleEntity       $role      Role Entity Instance.
     * @param AclResourceEntity[] $resources Resources that not added to acl yet.
     *
     * @return Acl
     */
    private function initRole(AclRoleEntity $role, &$resources)
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
         * @var AclResourceEntity $allowedResourceAction
         */
        foreach ($role->getResource() as $allowedResourceAction) {
            if (array_key_exists($allowedResourceAction->getId(), $resources)) {
                $this->allowAccess($role->getCode(), $allowedResourceAction, $resources);
                unset($resources[$allowedResourceAction->getId()]);
            }
        }

        return $this->acl;
    }

    /**
     * Allow access to resource $resource and it's children for role $role
     *
     * @param string              $role
     * @param AclResourceEntity   $resource
     * @param AclResourceEntity[] $resources
     */
    private function allowAccess($role, AclResourceEntity $resource, &$resources)
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

    /**
     * Disallow access to resource $resource and it's children for role $role
     *
     * @param string              $role
     * @param AclResourceEntity   $resource
     * @param AclResourceEntity[] $resources
     */
    private function disallowAccess($role, AclResourceEntity $resource, &$resources)
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

    /**
     * Add resource to ACL if it is not added yet.
     *
     * @param string $resourceCode
     */
    private function addResource($resourceCode)
    {
        if (!$this->acl->hasResource($resourceCode)) {
            $this->acl->addResource($resourceCode);
        }
    }

    /**
     * Gets resource code from entity instance.
     *
     * @param AclResourceEntity $resource
     *
     * @return string
     */
    private function getResourceCode(AclResourceEntity $resource)
    {
        return $resource->getModule() . Checker::RESOURCE_PARTS_JOIN_BY . $resource->getResource();
    }

    /**
     * Gets current user roles is set.
     * This method uses Service manager "AccessControl\Roles" alias. You should set factory for this alias somewhere
     * in your project, that would return array of current roles to use for acl.
     *
     * @return AclRoleEntity[]
     */
    private function getRoles()
    {
        return $this->getServiceLocator()->get('AccessControl\Roles');
    }

    /**
     * Gets all resources currently added to acl.
     *
     * @return \AccessControl\Entity\AclResource[]
     */
    private function getResources()
    {
        if ($this->resources == null) {
            /**
             * @var AclResourceEntity[] $resources
             */
            $resources = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager')->getRepository('AccessControl\Entity\AclResource')->findAll();
            foreach ($resources as $res) {
                $this->resources[$res->getId()] = $res;
            }
        }

        return $this->resources;
    }
}
