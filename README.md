# AccessControl module

To install module add sbx-zf2/access-control to require section in your composer.json.
Here an example:
```
"require": {
	"php": ">=5.4",
	"zendframework/zendframework": "2.3.*",
	"zendframework/zend-developer-tools": "dev-master",
	"doctrine/doctrine-orm-module": "0.8.*",
	"bjyoungblood/BjyProfiler": "*",
	"sbx-zf2/access-control":"1.*"
},
```

To add controller to ACL control, you should implenet to it \AccessControl\Mvc\Provider\ControllerInterface. You also can use \AccessControl\Mvc\Provider\ControllerTrait, that have implementation for method defined in interface.

To add Module to ACL control, you should implement \AccessControl\Mvc\Provider\ModuleInterface to Module class. You also can use  \AccessControl\Mvc\Provider\ModuleTrait.

System would collect acl only from controllers with ControllerInterface inside of modules with ModuleInterface. If you would not implement ModuleInterface, all controllers of that module would be ignored by ACL system.

You can use annotations to configure
 - access type public/protectedByAcl
 - parent acl resource in format {module:resource:privilege}

Example of annotaton for controller/action
/**
 * The some method of my controller
 *
 * @AccessControl\public true
 * @AccessControl\parent auth:registration:index
 */
