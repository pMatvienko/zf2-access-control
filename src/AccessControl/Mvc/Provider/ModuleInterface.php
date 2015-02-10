<?php
namespace AccessControl\Mvc\Provider;

interface ModuleInterface
{
    const RESOURCE_INTERFACE = 'AccessControl\Mvc\Provider\ControllerInterface';
    public function getModuleAclList();
}