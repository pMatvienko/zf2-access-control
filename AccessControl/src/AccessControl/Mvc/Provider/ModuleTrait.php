<?php
namespace AccessControl\Mvc\Provider;

trait ModuleTrait
{
    public function getControllerList()
    {
        $config = $this->getConfig();
        return $config['controllers']['invokables'];
    }

    public function getModuleAclList()
    {
        $module = strtolower(substr(__CLASS__, 0, -7));

        $moduleResources = array();
        $controllers = $this->getControllerList();
        foreach($controllers as $controllerAlias=>$controllerClass) {
            $controllerResources = $this->scanControllerAclResources($controllerClass, $module);
            if($controllerResources != false)
            {
                $moduleResources += $controllerResources;
            }
        }
        return $moduleResources;
    }

    private function scanControllerAclResources($controllerClass)
    {
        /**
         * @var \ReflectionClass $controllerClassReflection
         */
        $controllerReflection = new \ReflectionClass($controllerClass);
        if(!array_key_exists(ModuleInterface::RESOURCE_INTERFACE, $controllerReflection->getInterfaces())) return false;
        $resourceName = strtolower(
            preg_replace(
                '/([^._])([A-Z]{1,1})/',
                '$1-$2',
                substr($controllerReflection->getShortName(), 0, -strlen(ControllerInterface::CONTROLLER_SUFFIX))
            )
        );
        $privileges = $controllerClass::getAclActions();
        if(empty($privileges)) {
            return false;
        }
        return array(
            $resourceName => $privileges
        );
    }
}