<?php

namespace Acme\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Acme\UserBundle\Entity\Permission
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Permission
{
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\Id
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;
    
    /**
     * @ORM\ManyToOne(targetEntity="\Supplier\SupplierBundle\Entity\Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $company;
    
    /**
	 * @ORM\ManyToMany(targetEntity="\Supplier\SupplierBundle\Entity\Restaurant")
     * @ORM\JoinTable(name="users_restaurants",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="user_id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="restaurant_id", referencedColumnName="id")}
     *      )
     */
    protected $restaurants;
    
    public function __construct()
    {
        $this->restaurants = new ArrayCollection();
    }

    /**
     * Set user
     *
     * @param Acme\UserBundle\Entity\User $user
     */
    public function setUser(\Acme\UserBundle\Entity\User $user)
    {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return Acme\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set company
     *
     * @param Supplier\SupplierBundle\Entity\Company $company
     */
    public function setCompany(\Supplier\SupplierBundle\Entity\Company $company)
    {
        $this->company = $company;
    }

    /**
     * Get company
     *
     * @return Supplier\SupplierBundle\Entity\Company 
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Add restaurants
     *
     * @param Supplier\SupplierBundle\Entity\Restaurant $restaurants
     */
    public function addRestaurant(\Supplier\SupplierBundle\Entity\Restaurant $restaurants)
    {
        $this->restaurants[] = $restaurants;
    }

    /**
     * Get restaurants
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getRestaurants()
    {
        return $this->restaurants;
    }

    /**
     * Clean restaurants
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function cleanRestaurants()
    {
        return $this->restaurants = new ArrayCollection();
    }
}