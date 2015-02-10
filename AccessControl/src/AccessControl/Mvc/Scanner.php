<?php
namespace AccessControl\Mvc;

use AccessControl\Mvc\Provider\ModuleInterface;
use AccessControl\Mvc\Provider;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Scanner implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

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



    public function getModulesList()
    {
        return $this->getModuleManager()->getModules();
    }

    /**
     * @return \Zend\ModuleManager\ModuleManager
     */
    protected function getModuleManager()
    {
        return $this->getServiceLocator()->get('Zend/ModuleManager/ModuleManager');
    }
}