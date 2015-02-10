You can use annotations to configure
 - access type public/protectedByAcl
 - parent acl resource in format {module:resource:privilege}

Example of annotaton for controller/action
/**
 * The some method of my controller
 *
 * @AccessControl\protected false
 * @AccessControl\parent auth:registration:index
 */