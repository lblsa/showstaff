<?php

namespace Acme\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Acme\UserBundle\Entity\Shift
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Shift
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
     * @var text $date
     *
     * @ORM\Column(name="date", type="text")
     */
    private $date;

    /**
     * @var boolean $agreed
     *
     * @ORM\Column(name="agreed", type="boolean")
     */
    private $agreed;

    /**
     * @ORM\ManyToOne(targetEntity="\Supplier\SupplierBundle\Entity\Restaurant")
     * @ORM\JoinColumn(name="restaurant_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $restaurant;

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
     * Set date
     *
     * @param text $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * Get date
     *
     * @return text 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set agreed
     *
     * @param boolean $agreed
     */
    public function setAgreed($agreed)
    {
        $this->agreed = $agreed;
    }

    /**
     * Get agreed
     *
     * @return boolean 
     */
    public function getAgreed()
    {
        return $this->agreed;
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
}