<?php
namespace AccessControl\Mvc\Provider;

/**
 * Class ModuleTrait
 *
 * @package AccessControl\Mvc\Provider
 * @author  Pavel Matviienko
 */
trait ModuleTrait
{
    /**
     * Gets a controllers list from module configuration file (['controllers']['invokables']).
     *
     * @return array
     */
    protected function getControllerList()
    {
        $config = $this->getConfig();
        return $config['controllers']['invokables'];
    }



    /**
     * An implementation for ModuleInterface:getModuleAclList.
     *
     * @return array
     */
    public function getModuleAclList()
    {
        $module = strtolower(substr(__CLASS__, 0, -7));

        $moduleResources = array();
        $controllers = $this->getControllerList();
        foreach ($controllers as $controllerAlias => $controllerClass) {
            $moduleResources += $this->scanControllerAclResources($controllerClass, $module);
        }

        return $moduleResources;
    }

    public function getAccessControlResourcesConfig()
    {
        return array();
    }

    /**
     *
     * @param $controllerClass
     *
     * @return array|bool
     */
    private function scanControllerAclResources($controllerClass)
    {
        /**
         * @var \ReflectionClass $controllerClassReflection
         */
        $controllerReflection = new \ReflectionClass($controllerClass);
        if (!array_key_exists(ModuleInterface::RESOURCE_INTERFACE, $controllerReflection->getInterfaces())) {
            return array();
        }
        $resourceName = strtolower(
            preg_replace(
                '/([^._])([A-Z]{1,1})/',
                '$1-$2',
                substr($controllerReflection->getShortName(), 0, -strlen(ControllerInterface::CONTROLLER_SUFFIX))
            )
        );
        $privileges = $controllerClass::getAclActions();
        if (empty($privileges)) {
            return array();
        }

        return array(
            $resourceName => $privileges
        );
    }
}