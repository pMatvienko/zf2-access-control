<?php
namespace AccessControl\Mvc;

use AccessControl\Mvc\Provider\ModuleInterface;
use AccessControl\Mvc\Provider;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Class Scanner.
 *
 * This class generalizes the functionality of scanning your project for acl resources.
 *
 * @package AccessControl\Mvc
 * @author  Pavel Matviienko
 */
class Scanner implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Scanning project and getting all acl resources and privileges.
     *
     * @return array
     */
    public function scan()
    {
        $resources = array();
        foreach($this->getModulesList() as $module)
        {
            $moduleInstance = $this->getModuleManager()->loadModule($module);

            if(($moduleInstance instanceof ModuleInterface)) {
                $moduleResources = $moduleInstance->getModuleAclList();
                if(!empty($moduleResources)) {
                    $resources[strtolower($module)] = $moduleResources;
                }
            }
        }
        return $resources;
    }

    /**
     * Gets an available modules list.
     *
     * @return array
     */
    public function getModulesList()
    {
        return $this->getModuleManager()->getModules();
    }

    /**
     * Gets a module manager.
     *
     * @return \Zend\ModuleManager\ModuleManager
     */
    protected function getModuleManager()
    {
        return $this->getServiceLocator()->get('Zend/ModuleManager/ModuleManager');
    }
}