<?php

namespace Acme\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Acme\UserBundle\Entity\WorkingHours
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class WorkingHours
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer $planhours
     *
     * @ORM\Column(name="planhours", type="integer", length=2)
	 * @Assert\Max(limit=24, message="Вы не можете запланировать более {{ limit }} часов")
     */
    private $planhours;

    /**
     * @var integer $facthours
     *
     * @ORM\Column(name="facthours", type="integer", length=2)
	 * @Assert\Max(limit=24, message="Нельзя работать более {{ limit }} часов в сутки")
     */
    private $facthours;

    /**
     * @var string $date
     *
     * @ORM\Column(name="date", type="string", length=10)
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity="\Supplier\SupplierBundle\Entity\Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $company;
    
    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;
    
    /**
     * @ORM\ManyToOne(targetEntity="\Supplier\SupplierBundle\Entity\Restaurant")
     * @ORM\JoinColumn(name="restaurant_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $restaurant;
    
    /**
     * @var integer $description
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;

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
     * Set planhours
     *
     * @param integer $planhours
     */
    public function setPlanhours($planhours)
    {
        $this->planhours = $planhours;
    }

    /**
     * Get planhours
     *
     * @return integer 
     */
    public function getPlanhours()
    {
        return $this->planhours;
    }

    /**
     * Set facthours
     *
     * @param integer $facthours
     */
    public function setFacthours($facthours)
    {
        $this->facthours = $facthours;
    }

    /**
     * Get facthours
     *
     * @return integer 
     */
    public function getFacthours()
    {
        return $this->facthours;
    }

    /**
     * Set date
     *
     * @param string $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * Get date
     *
     * @return string 
     */
    public function getDate()
    {
        return $this->date;
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
     * Set restaurant
     *
     * @param Supplier\SupplierBundle\Entity\Restaurant $restaurant
     */
    public function setRestaurant(\Supplier\SupplierBundle\Entity\Restaurant $restaurant)
    {
        $this->restaurant = $restaurant;
    }

    /**
     * Get restaurant
     *
     * @return Supplier\SupplierBundle\Entity\Restaurant 
     */
    public function getRestaurant()
    {
        return $this->restaurant;
    }

    /**
     * Set description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }
}