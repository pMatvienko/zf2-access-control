<?php
namespace AccessControl\Mvc\Provider;

/**
 * Interface ModuleInterface.
 *
 * THis is an acl provider interface for your module. If you want to cover module with accessControl - implement
 * this interface to Module class (Module.php) of your module. If you would not implement this interface
 * all controllers will be ignored on scan and on acl check even if that controllers
 * implements ControllerInterface.
 *
 * I suggest to also use ModuleTrait, that already have an implementation for "getModuleAclList" action.
 *
 * @package AccessControl\Mvc\Provider
 * @author  Pavel Matviienko
 */
interface ModuleInterface
{
    const RESOURCE_INTERFACE = 'AccessControl\Mvc\Provider\ControllerInterface';

    /**
     * get's resources(controllers) and actions(methods) list from your module.
     * @return mixed
     */
    public function getModuleAclList();
}