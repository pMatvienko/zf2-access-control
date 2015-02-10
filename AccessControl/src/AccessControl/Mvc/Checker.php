<?php
namespace AccessControl\Mvc;

use Zend\Mvc\ModuleRouteListener;

class Checker
{
    const RESOURCE_PARTS_JOIN_BY = '-';
    const SECTION_PRIVILEGE = 'section';
    const COMBINED_ROLE = 'combined';


    /**
     * @param \Zend\Mvc\MvcEvent $e
     * @throws \Zend\Mvc\Exception\InvalidControllerException
     */
    public function onDispatch(\Zend\Mvc\MvcEvent $e)
    {
        $sm = $e->getApplication()->getServiceManager();
        if($e->getTarget() instanceof \AccessControl\Mvc\Provider\ControllerInterface){
            $resource = $this->getResourceCode($e->getRouteMatch());
            $action = $this->getActionCode($e->getRouteMatch());
            /**
             * @var \AccessControl\Acl\Acl $acl
             */
            $acl = $sm->get('AccessControl\Acl');
            if($acl->hasResource($resource) && !$acl->isAllowed(self::COMBINED_ROLE, $resource, $action)){
                throw new \Zend\Mvc\Exception\InvalidControllerException('Action disallowed by access control rules');
            }
        }
    }

    private function getResourceCode($routeMatch)
    {
        $routeMatchParams = $routeMatch->getParams();
        $controllerNameParts = explode('\\', $routeMatchParams['controller']);
        $module = $this->convertCode(lcfirst($controllerNameParts[0]));
        $controller = $this->convertCode(lcfirst($controllerNameParts[count($controllerNameParts) - 1]));
        return $module . self::RESOURCE_PARTS_JOIN_BY . $controller;
    }

    public function getActionCode($routeMatch)
    {
        $routeMatchParams = $routeMatch->getParams();
        return $this->convertCode($routeMatchParams['action']);
    }

    public function convertCode($code)
    {
        return strtolower(
            preg_replace(
                '/([^._])([A-Z]{1,1})/',
                '$1-$2',
                $code
            )
        );
    }
}