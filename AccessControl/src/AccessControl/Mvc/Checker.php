<?php
namespace AccessControl\Mvc;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Http\RouteMatch;

/**
 * Class Checker. This class checking access on DISPATCH EVENT.
 *
 * @package AccessControl\Mvc
 * @author  Pavel Matviienko
 */
class Checker
{
    const RESOURCE_PARTS_JOIN_BY = '-';
    const SECTION_PRIVILEGE = 'section';
    const COMBINED_ROLE = 'combined';


    /**
     * Event callback method.
     *
     * @param MvcEvent $e
     *
     * @throws Exception\DisallowedException
     * @throws Exception\NoRouteMatchException
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
        if(null == $routeMatch){
            throw new Exception\NoRouteMatchException('Can not get RouteMatch to define currently requested controller and action.');
        }
        $sm = $e->getApplication()->getServiceManager();
        $resource = $this->getResourceCode($routeMatch);
        $action = $this->getActionCode($routeMatch);
        /**
         * @var \AccessControl\Acl\Acl $acl
         */
        $acl = $sm->get('AccessControl/Acl');
        if($acl->hasResource($resource) && !$acl->isAllowed(self::COMBINED_ROLE, $resource, $action)){
            throw new Exception\DisallowedException('Action disallowed by access control rules');
        }
    }

    /**
     * Gets a resource code from routeMatch.
     *
     * @param RouteMatch $routeMatch
     *
     * @return string
     * @throws Exception\NoControllerException
     */
    private function getResourceCode(RouteMatch $routeMatch)
    {
        $routeMatchParams = $routeMatch->getParams();
        if(empty($routeMatchParams['controller'])){
            throw new Exception\NoControllerException('Can not get currently requested controller from route match');
        }
        $controllerNameParts = explode('\\', $routeMatchParams['controller']);
        $module = $this->convertCode(lcfirst($controllerNameParts[0]));
        $controller = $this->convertCode(lcfirst($controllerNameParts[count($controllerNameParts) - 1]));

        return $module . self::RESOURCE_PARTS_JOIN_BY . $controller;
    }

    /**
     * Gets a privilege code from routeMatch.
     *
     * @param RouteMatch $routeMatch
     *
     * @return string
     */
    private function getActionCode(RouteMatch $routeMatch)
    {
        $routeMatchParams = $routeMatch->getParams();
        if(empty($routeMatchParams['action'])){
            throw new Exception\NoControllerException('Can not get currently requested action from route match');
        }
        return $this->convertCode($routeMatchParams['action']);
    }

    /**
     * Converting code from camel case to part1-part2-part3 format.
     *
     * @param string $code
     *
     * @return string
     */
    private function convertCode($code)
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