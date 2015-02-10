<?php
namespace AccessControl\Acl;

class Acl extends \Zend\Permissions\Acl\Acl
{
    public function isAllowed($role = null, $resource = null, $privilege = null)
    {
        return parent::isAllowed($role, $resource, $privilege);
    }
}