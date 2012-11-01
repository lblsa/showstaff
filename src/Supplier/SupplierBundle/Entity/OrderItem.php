<?php

namespace Supplier\SupplierBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Supplier\SupplierBundle\Entity\OrderItem
 *
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="uniq", columns={"booking_date", "company_id", "restaurant_id", "product_id"})})
 * @ORM\Entity
 */
class OrderItem
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
     * @var string $date
     *
     * @ORM\Column(name="booking_date", type="string", length=10, nullable=false)
     */
    private $date;
    
    /**
     * @ORM\ManyToOne(targetEntity="Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=false)
     */
    protected $company;

    /**
     * @ORM\ManyToOne(targetEntity="Restaurant")
     * @ORM\JoinColumn(name="restaurant_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $restaurant;

    /**
     * @ORM\ManyToOne(targetEntity="Supplier")
     * @ORM\JoinColumn(name="supplier_id", referencedColumnName="id", nullable=false)
     */
    private $supplier;

    /**
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $product;

    /**
     * @var integer $amount
     *
     * @ORM\Column(name="amount", type="float", length=11, nullable=false)
	 * @Assert\NotBlank()
     */
    private $amount;


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
     * Set amount
     *
     * @param integer $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Get amount
     *
     * @return integer 
     */
    public function getAmount()
    {
        return $this->amount;
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
     * Set supplier
     *
     * @param Supplier\SupplierBundle\Entity\Supplier $supplier
     */
    public function setSupplier(\Supplier\SupplierBundle\Entity\Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    /**
     * Get supplier
     *
     * @return Supplier\SupplierBundle\Entity\Supplier 
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Set product
     *
     * @param Supplier\SupplierBundle\Entity\Product $product
     */
    public function setProduct(\Supplier\SupplierBundle\Entity\Product $product)
    {
        $this->product = $product;
    }

    /**
     * Get product
     *
     * @return Supplier\SupplierBundle\Entity\Product 
     */
    public function getProduct()
    {
        return $this->product;
    }
}
