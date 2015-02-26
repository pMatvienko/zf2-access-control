<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace AccessControl;

use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\EventInterface;

class Module implements BootstrapListenerInterface, AutoloaderProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * Listen to the bootstrap event
     *
     * @param EventInterface $e
     * @return array
     */
    public function onBootstrap(EventInterface $e)
    {
        $app = $e->getApplication();
        $config = $app->getServiceManager()->get('config');
        if (!empty($config['access_control']['enabled'])) {
            $checker = new \AccessControl\Mvc\Checker();
            $app->getEventManager()->getSharedManager()->attach('Zend\Mvc\Controller\AbstractActionController', MvcEvent::EVENT_DISPATCH, array($checker, 'onDispatch'), 100);
        }
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'AccessControl\Roles' => function ($sm) {
                    return $sm->get('Doctrine\ORM\EntityManager')->getRepository('AccessControl\Entity\AclRole')->findBy(
                        array('applyToGuest' => true)
                    );
                },
            )
        );
    }
}
