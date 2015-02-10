<?php
namespace AccessControl\Mvc\Provider;

interface ControllerInterface
{
    const CONTROLLER_SUFFIX = 'Controller';
    const METHOD_SUFFIX = 'Action';

    const ANNOTATION_PROTECTED = 'protected';
    const ANNOTATION_PARENT = 'parent';

    /**
     * @return mixed
     */
    public function getAclActions();
}