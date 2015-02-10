<?php

namespace AccessControl\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AclRole
 *
 * @ORM\Table(name="acl_role", indexes={@ORM\Index(name="FK_acl_roles_parent", columns={"parent_id"})})
 * @ORM\Entity
 */
class AclRole
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
     * @ORM\Column(name="code", type="string", length=50, nullable=false)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=false)
     */
    private $type = 'system';

    /**
     * @var boolean
     *
     * @ORM\Column(name="apply_to_new_user", type="boolean", nullable=false)
     */
    private $applyToNewUser = '0';

    /**
     * @var boolean
     *
     * @ORM\Column(name="apply_to_guest", type="boolean", nullable=false)
     */
    private $applyToGuest = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="access_level", type="string", nullable=false)
     */
    private $accessLevel = 'protected';

    /**
     * @var \AccessControl\Entity\AclRole
     *
     * @ORM\ManyToOne(targetEntity="AccessControl\Entity\AclRole")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    private $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="AccessControl\Entity\AclResource", inversedBy="role")
     * @ORM\JoinTable(name="acl_role_resources",
     *   joinColumns={
     *     @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="resource_id", referencedColumnName="id")
     *   }
     * )
     */
    private $resource;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->resource = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set code
     *
     * @param string $code
     * @return AclRole
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return AclRole
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
     * Set applyToNewUser
     *
     * @param boolean $applyToNewUser
     * @return AclRole
     */
    public function setApplyToNewUser($applyToNewUser)
    {
        $this->applyToNewUser = $applyToNewUser;

        return $this;
    }

    /**
     * Get applyToNewUser
     *
     * @return boolean 
     */
    public function getApplyToNewUser()
    {
        return $this->applyToNewUser;
    }

    /**
     * Set applyToGuest
     *
     * @param boolean $applyToGuest
     * @return AclRole
     */
    public function setApplyToGuest($applyToGuest)
    {
        $this->applyToGuest = $applyToGuest;

        return $this;
    }

    /**
     * Get applyToGuest
     *
     * @return boolean 
     */
    public function getApplyToGuest()
    {
        return $this->applyToGuest;
    }

    /**
     * Set accessLevel
     *
     * @param string $accessLevel
     * @return AclRole
     */
    public function setAccessLevel($accessLevel)
    {
        $this->accessLevel = $accessLevel;

        return $this;
    }

    /**
     * Get accessLevel
     *
     * @return string 
     */
    public function getAccessLevel()
    {
        return $this->accessLevel;
    }

    /**
     * Set parent
     *
     * @param \AccessControl\Entity\AclRole $parent
     * @return AclRole
     */
    public function setParent(\AccessControl\Entity\AclRole $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \AccessControl\Entity\AclRole
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add resource
     *
     * @param \AccessControl\Entity\AclResource $resource
     * @return AclRole
     */
    public function addResource($resource)
    {
        if($resource instanceof \AccessControl\Entity\AclResource){
            $this->resource[] = $resource;
        } elseif ($resource instanceof \Doctrine\Common\Collections\Collection) {
            foreach($resource as $res) {
                $res->addRole($this);
                $this->resource[] = $res;
            }
        }

        return $this;
    }

    /**
     * Remove resource
     *
     * @param \AccessControl\Entity\AclResource $resource
     */
    public function removeResource($resource)
    {
        if($resource instanceof \AccessControl\Entity\AclResource){
            $this->resource->removeElement($resource);
        } elseif ($resource instanceof \Doctrine\Common\Collections\Collection) {
            foreach($resource as $res) {
                $res->removeRole($this);
                $this->resource->removeElement($res);
            }
        }

    }

    /**
     * Get resource
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getResource()
    {
        return $this->resource;
    }
}
