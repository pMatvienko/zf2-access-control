<?php
namespace AccessControl\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use AccessControl\Mvc\Provider\ControllerInterface as AclControllerInterface;
use AccessControl\Entity\AclResource as AclResourceEntity;
use AccessControl\Entity\AclRole AS AclRoleEntity;

/**
 * Class AclModel
 *
 * @package AccessControl\Model
 * @author  Pavel Matviienko
 */
class AclModel implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const RESOURCE_CODE_SPLITTER = ':';

    /**
     * Gets role entity instance by provided id.
     *
     * @param integer $id
     *
     * @return null|AclRoleEntity
     */
    public function findRoleById($id)
    {
        return $this->getEntityManager()->getRepository('AccessControl\Entity\AclRole')->find($id);
    }

    /**
     * Removing role entity.
     *
     * @param AclRoleEntity $entity
     */
    public function removeRole(AclRoleEntity $entity)
    {
        return $this->getEntityManager()->remove($entity);
    }

    /**
     * This methods gets an acl resources and privileges list from your project structure and adding it all to database.
     * It also would update current resources information if needed and remove resources and privileges
     * that are not needed anymore.
     *
     * @throws ParentAclResourceNotFoundException
     */
    public function syncMvcResources()
    {
        $scanner = $this->getServiceLocator()->get('AccessControl\Mvc\Scanner');

        $storedResources = array();
        /**
         * @var AclResourceEntity $resource
         */
        foreach (
            $this->getEntityManager()
                ->getRepository('AccessControl\Entity\AclResource')
                ->findBy(array('type' => AclControllerInterface::RESOURCE_TYPE_MVC)) as $resource) {
            $storedResources[$resource->getModule() . self::RESOURCE_CODE_SPLITTER . $resource->getResource() . self::RESOURCE_CODE_SPLITTER . $resource->getPrivilege()] = $resource;
        }

        $resourceParents = array();
        /**
         * \AccessControl\Entity\AclResource[] $allMvcResources
         */
        $allMvcResources = array();

        foreach ($scanner->scan() as $module => $mvcResources) {
            foreach ($mvcResources as $mvcResource => $privileges) {
                foreach ($privileges as $privilege => $properties) {
                    $resourceCode = $module . self::RESOURCE_CODE_SPLITTER . $mvcResource . self::RESOURCE_CODE_SPLITTER . $privilege;
                    if (array_key_exists($resourceCode, $storedResources)) {
                        $allMvcResources[$resourceCode] = $storedResources[$resourceCode];
                        $allMvcResources[$resourceCode]->setIsPublic($properties[AclControllerInterface::ANNOTATION_PUBLIC]);
                        unset($storedResources[$resourceCode]);
                    } else {
                        $newResource = new AclResourceEntity();
                        $newResource->setModule($module);
                        $newResource->setResource($mvcResource);
                        $newResource->setPrivilege($privilege);
                        $newResource->setType($properties[AclControllerInterface::RESOURCE_TYPE_PARAM]);
                        $newResource->setIsPublic($properties[AclControllerInterface::ANNOTATION_PUBLIC]);
                        $allMvcResources[$resourceCode] = $newResource;
                    }
                    $this->getEntityManager()->persist($allMvcResources[$resourceCode]);
                    if (!empty($properties[AclControllerInterface::ANNOTATION_PARENT])) {
                        $resourceParents[$resourceCode] = $properties[AclControllerInterface::ANNOTATION_PARENT];
                    }
                }
            }
        }
        foreach ($storedResources as $unusedResource) {
            $this->getEntityManager()->remove($unusedResource);
        }
        $this->getEntityManager()->flush();

        foreach ($allMvcResources as $code => $resource) {
            if ($resource->getParent() != null && !array_key_exists($code, $resourceParents)) {
                $resource->setParent(null);
                $this->getEntityManager()->persist($resource);
            }
        }

        foreach ($resourceParents as $child => $parent) {
            if (!array_key_exists($parent, $allMvcResources)) {
                throw new ParentAclResourceNotFoundException('Resource "' . $parent . '" not found, but was mentioned for resource "' . $child . '"');
            }
            $allMvcResources[$child]->setParent($allMvcResources[$parent]);
            $this->getEntityManager()->persist($allMvcResources[$child]);
        }
        $this->getEntityManager()->flush();
        $this->flushCache();
    }

    /**
     * Flushing cache.
     *
     * @param string[] $tags Provide role codes, that you want to clear cache for. If you would left this param empty, method will flush cache for all roles.
     *
     * @return bool
     */
    public function flushCache($tags = null)
    {
        if ($this->getServiceLocator()->has('AccessControl\Acl\Cache')) {
            /**
             * @var \Zend\Cache\Storage\Adapter\Filesystem $cache ;
             */
            $cache = $this->getServiceLocator()->get('AccessControl\Acl\Cache');
            if (null === $tags) {
                return $cache->flush();
            } else {
                return $cache->clearByTags($tags);
            }
        }
    }

    /**
     * Gets a doctrine entity manager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
    }

}