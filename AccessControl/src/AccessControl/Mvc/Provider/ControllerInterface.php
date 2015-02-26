<?php
namespace AccessControl\Mvc\Provider;

/**
 * Interface ControllerInterface.
 *
 * This is an acl provider interface for your controller. Implement it if you want to add controller to acl control system.
 * Most likely you also would need to use ControllerTrait, that contains implementation for method "getAclActions".
 *
 * @package AccessControl\Mvc\Provider
 * @author  Pavel Matviienko
 */
interface ControllerInterface
{
    const CONTROLLER_SUFFIX = 'Controller';
    const METHOD_SUFFIX = 'Action';

    const ANNOTATION_PUBLIC = 'public';
    const ANNOTATION_PARENT = 'parent';

    const RESOURCE_TYPE_PARAM = 'resourceType';
    const RESOURCE_TYPE_MVC = 'mvc';

    /**
     * Gets all available actions(privileges) for current controller.
     *
     * @return array
     */
    public function getAclActions();
}