<?php
namespace AccessControl;

return [
    'service_manager' => [
        'invokables' => [
            'AccessControl\Mvc\Scanner' => 'AccessControl\Mvc\Scanner',
        ],
        'factories'          => [
            'AccessControl\Acl' => 'AccessControl\Acl\Factory',
            'AccessControl\Acl\Cache' => function() {
                return \Zend\Cache\StorageFactory::factory(
                    array(
                        'adapter' => array(
                            'name' => 'filesystem',
                            'options' => array(
                                'dirLevel' => 2,
                                'cacheDir' => 'data/cache/'.__NAMESPACE__,
                                'dirPermission' => 0755,
                                'filePermission' => 0666,
                                'namespaceSeparator' => '-'
                            ),
                        ),
                        'plugins' => array('serializer'),
                    )
                );
            }
        ],
        'aliases' => array(
            'acl' => 'AccessControl\Acl',
        ),
    ],
    'console'         => [
        'router' => [
            'routes' => [],
        ],
    ],
    'doctrine'        => [
        'driver' => [
            __NAMESPACE__ . '_driver' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__ . '/../src/' . __NAMESPACE__ . '/Entity']
            ],
            'orm_default' => [
                'drivers' => [
                    __NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_driver'
                ]
            ]
        ]
    ],
    'access_control' => [
        'mvc_acl' => [
            'enabled' => true
        ]
    ],
];