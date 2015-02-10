<?php
namespace AccessControl\Mvc\Provider;

trait ControllerTrait
{
    private static $systemActions = array(
        'notFoundAction',
        'getMethodFromAction'
    );
    /**
     * @return mixed
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
            ) continue;

            $privilege = strtolower(preg_replace(
                '/([^._])([A-Z]{1,1})/',
                '$1-$2',
                substr($methodName, 0, -strlen(self::METHOD_SUFFIX))
            ));
            $resources[$privilege] = self::parseDocComment($methodInfo->getDocComment());
        }
        return $resources;
    }

    private static function parseDocComment($docComment)
    {
        $default = array(
            ControllerInterface::ANNOTATION_PROTECTED => true,
            ControllerInterface::ANNOTATION_PARENT => null
        );
        if(empty($docComment)) {
            return $default;
        }
        preg_match_all('#@AccessControl[\\\/](.*?)\n#s', $docComment, $annotations);
        foreach($annotations[1] as $row) {
            $row = explode(' ', $row);
            switch($row[0]) {
                case ControllerInterface::ANNOTATION_PROTECTED:
                    $default[ControllerInterface::ANNOTATION_PROTECTED] = ($row[1] == 'false') ? false : true;
                    break;
                case ControllerInterface::ANNOTATION_PARENT:
                    $default[ControllerInterface::ANNOTATION_PARENT] = empty($row[1]) ? null : $row[1];
                    break;
                default:
                    throw new \AccessControl\Mvc\Exception\AnnotationNotSupportedException('Annotation "'.$row[0].'" is not supported by AccessControll module');
            }
        }
        return $default;
    }

    /**
     * @return array
     */
    public static function getSystemActions()
    {
        return self::$systemActions;
    }
}