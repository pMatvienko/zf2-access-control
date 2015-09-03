<?php

namespace AccessControl\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AclResource
 *
 * @ORM\Table(name="acl_resource", indexes={@ORM\Index(name="idx_acl_resource", columns={"parent_id"})})
 * @ORM\Entity
 */
class AclResource
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255, nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="module", type="string", length=255, nullable=false)
     */
    private $module;

    /**
     * @var string
     *
     * @ORM\Column(name="resource", type="string", length=255, nullable=false)
     */
    private $resource;

    /**
     * @var string
     *
     * @ORM\Column(name="privilege", type="string", length=255, nullable=false)
     */
    private $privilege;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_public", type="boolean", nullable=false)
     */
    private $isPublic;

    /**
     * @var \AccessControl\Entity\AclResource
     *
     * @ORM\ManyToOne(targetEntity="AccessControl\Entity\AclResource")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    /**
     * @var \AccessControl\Entity\AclResource[]
     *
     * @ORM\OneToMany(targetEntity="AclResource", mappedBy="parent")
     */
    private $children;


    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AccessControl\Entity\AclRole", mappedBy="resource")
     */
    private $role;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->role = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return AclResource
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set resource
     *
     * @param string $resource
     * @return AclResource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get resource
     *
     * @return string 
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set privilege
     *
     * @param string $privilege
     * @return AclResource
     */
    public function setPrivilege($privilege)
    {
        $this->privilege = $privilege;

        return $this;
    }

    /**
     * Get privilege
     *
     * @return string 
     */
    public function getPrivilege()
    {
        return $this->privilege;
    }

    /**
     * Set isPublic
     *
     * @param boolean $isPublic
     * @return AclResource
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic
     *
     * @return boolean 
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * Set parent
     *
     * @param \AccessControl\Entity\AclResource $parent
     * @return AclResource
     */
    public function setParent(\AccessControl\Entity\AclResource $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AccessControl\Entity\AclResource
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add children
     *
     * @param \AccessControl\Entity\AclResource $children
     * @return AclResource
     */
    public function addChild(\AccessControl\Entity\AclResource $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \AccessControl\Entity\AclResource $children
     */
    public function removeChild(\AccessControl\Entity\AclResource $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add role
     *
     * @param \AccessControl\Entity\AclRole $role
     * @return AclResource
     */
    public function addRole(\AccessControl\Entity\AclRole $role)
    {
        $this->role[] = $role;

        return $this;
    }

    /**
     * Remove role
     *
     * @param \AccessControl\Entity\AclRole $role
     */
    public function removeRole(\AccessControl\Entity\AclRole $role)
    {
        $this->role->removeElement($role);
    }

    /**
     * Get role
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set module
     *
     * @param string $module
     * @return AclResource
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get module
     *
     * @return string 
     */
    public function getModule()
    {
        return $this->module;
    }
}
