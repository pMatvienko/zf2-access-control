<?php
namespace AccessControl\Mvc\Provider;

/**
 * Class ControllerTrait
 *
 * @package AccessControl\Mvc\Provider
 * @author  Pavel Matviienko
 */
trait ControllerTrait
{
    /**
     * Methods to skip on acl scan.
     *
     * @var array
     */
    private static $systemActions = array(
        'notFoundAction',
        'getMethodFromAction'
    );

    /**
     * Gets all available actions(privileges) for current controller.
     *
     * An implementation for ControllerInterface::getAclActions. You can use this trait, or write method by your own.
     *
     * @return array
     */
    public static function getAclActions()
    {
        $resources = array();
        $controllerClass = get_called_class();
        /**
         * @var \ReflectionClass $controllerClassReflection
         */
        $controllerReflection = new \ReflectionClass($controllerClass);
        /**
         * @var \ReflectionMethod $methodInfo
         */
        foreach ($controllerReflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodInfo) {
            $methodName = $methodInfo->getName();
            if (
                in_array($methodName, self::getSystemActions()) ||
                strlen($methodName) < strlen(ControllerInterface::METHOD_SUFFIX) ||
                substr($methodName, -strlen(ControllerInterface::METHOD_SUFFIX)) != ControllerInterface::METHOD_SUFFIX
            ) {
                continue;
            }

            $privilege = strtolower(preg_replace(
                '/([^._])([A-Z]{1,1})/',
                '$1-$2',
                substr($methodName, 0, -strlen(self::METHOD_SUFFIX))
            ));
            $resources[$privilege] = self::parseDocComment($methodInfo->getDocComment());
        }

        return $resources;
    }

    /**
     * Parsing method doc. block for "@AccessControl/..." tags.
     * You can use any tags you want, but currently module using only "@AccessControl/parent"
     * and "@AccessControl/public" for it's inner logic
     *
     * @param string $docComment
     *
     * @return array
     */
    private static function parseDocComment($docComment)
    {
        $default = array(
            ControllerInterface::ANNOTATION_PUBLIC => false,
            ControllerInterface::ANNOTATION_PARENT => null
        );
        if (empty($docComment)) {
            return $default;
        }
        preg_match_all('#@AccessControl[\\\/](.*?)\n#s', $docComment, $annotations);
        foreach ($annotations[1] as $row) {
            $row = explode(' ', $row);
            switch ($row[0]) {
                case ControllerInterface::ANNOTATION_PUBLIC:
                    $default[ControllerInterface::ANNOTATION_PUBLIC] = ($row[1] == 'false') ? false : true;
                    break;
                default:
                    $default[$row[0]] = empty($row[1]) ? null : trim($row[1]);
            }
        }
        $default[ControllerInterface::RESOURCE_TYPE_PARAM] = ControllerInterface::RESOURCE_TYPE_MVC;

        return $default;
    }

    /**
     * gets an action names list to skip on scan.
     *
     * @return array
     */
    public static function getSystemActions()
    {
        return self::$systemActions;
    }
}