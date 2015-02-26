<?php
namespace AccessControl\Mvc;

use AccessControl\Mvc\Provider\ControllerInterface as AclControllerInterface;
use Zend\Mvc\Exception\InvalidControllerException;
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
     * @throws InvalidControllerException
     */
    public function onDispatch(MvcEvent $e)
    {
        $sm = $e->getApplication()->getServiceManager();
        if ($e->getTarget() instanceof AclControllerInterface) {
            $resource = $this->getResourceCode($e->getRouteMatch());
            $action = $this->getActionCode($e->getRouteMatch());
            /**
             * @var \AccessControl\Acl\Acl $acl
             */
            $acl = $sm->get('AccessControl\Acl');
            if ($acl->hasResource($resource) && !$acl->isAllowed(self::COMBINED_ROLE, $resource, $action)) {
                throw new InvalidControllerException('Action disallowed by access control rules');
            }
        }
    }

    /**
     * Gets a resource code from routeMatch.
     *
     * @param RouteMatch $routeMatch
     *
     * @return string
     */
    private function getResourceCode(RouteMatch $routeMatch)
    {
        $routeMatchParams = $routeMatch->getParams();
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