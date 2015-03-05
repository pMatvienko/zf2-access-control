<?php
namespace AccessControl\Model;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use AccessControl\Mvc\Provider\ControllerInterface as AclControllerInterface;

/**
 * Class AclModel
 *
 * @package AccessControl\Model
 * @author  Pavel Matviienko
 */
class AclModel implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const RESOURCE_TYPE_MVC = 'mvc';
    const RESOURCE_CODE_SPLITTER = ':';

    /**
     * Gets role entity instance by provided id.
     *
     * @param integer $id
     *
     * @return null|\AccessControl\Entity\AclRole
     */
    public function findRoleById($id)
    {
        return $this->getEntityManager()->getRepository(get_class($this->getServiceLocator()->get('AccessControl/Entity/Role')))->find($id);
    }

    /**
     * Removing role entity.
     *
     * @param \AccessControl\Entity\AclRole $entity
     */
    public function removeRole($entity)
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
        $scanner = $this->getServiceLocator()->get('AccessControl/Mvc/Scanner');

        $storedResources = array();
        /**
         * @var \AccessControl\Entity\AclResource $resource
         */
        foreach (
            $this->getEntityManager()
                ->getRepository(get_class($this->getServiceLocator()->get('AccessControl/Entity/Resource')))
                ->findBy(array('type' => self::RESOURCE_TYPE_MVC)) as $resource) {
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
                        $allMvcResources[$resourceCode]->setIsPublic(!empty($properties[AclControllerInterface::ANNOTATION_PUBLIC]) ? true : false);
                        unset($storedResources[$resourceCode]);
                    } else {
                        $newResource = $this->getServiceLocator()->get('AccessControl/Entity/Resource');
                        $newResource->setModule($module);
                        $newResource->setResource($mvcResource);
                        $newResource->setPrivilege($privilege);
                        $newResource->setType(self::RESOURCE_TYPE_MVC);
                        $newResource->setIsPublic(!empty($properties[AclControllerInterface::ANNOTATION_PUBLIC]) ? true : false);
                        $allMvcResources[$resourceCode] = $newResource;
                    }
                    $this->getEntityManager()->persist($allMvcResources[$resourceCode]);
                    if (!empty($properties[AclControllerInterface::ANNOTATION_PARENT])) {
                        $resourceParents[$resourceCode] = $properties[AclControllerInterface::ANNOTATION_PARENT];
                    }
                }
            }
        }

        /**
         * @var \AccessControl\Entity\AclResource $unusedResource
         * @var \AccessControl\Entity\AclResource $child
         */
        foreach ($storedResources as $unusedResource) {
            $children = $unusedResource->getChildren();
            if(count($children) > 0){
                foreach($children as $child){
                    $child->setParent(null);
                    $this->getEntityManager()->persist($child);
                    $unusedResource->removeChild($child);
                    $this->getEntityManager()->flush();
                }
            }

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
                throw new Exception\ParentResourceNotFoundException('Resource "' . $parent . '" not found, but was mentioned for resource "' . $child . '"');
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
        if ($this->getServiceLocator()->has('AccessControl/Acl/Cache')) {
            /**
             * @var \Zend\Cache\Storage\Adapter\Filesystem $cache ;
             */
            $cache = $this->getServiceLocator()->get('AccessControl/Acl/Cache');
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