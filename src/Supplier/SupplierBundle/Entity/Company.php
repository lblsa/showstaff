<?php

namespace Supplier\SupplierBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Supplier\SupplierBundle\Entity\Company
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Supplier\SupplierBundle\Entity\CompanyRepository")
 */
class Company
{	
	
    /**
     * @ORM\OneToMany(targetEntity="Restaurant", mappedBy="company")
     */
    protected $restaurants;
	
    /**
     * @ORM\OneToMany(targetEntity="Product", mappedBy="company")
     */
    protected $products;
	
    /**
     * @ORM\OneToMany(targetEntity="Supplier", mappedBy="company")
     */
    protected $suppliers;
    

    public function __construct()
    {
        $this->restaurants = new ArrayCollection();
        $this->products = new ArrayCollection();
    }	
	
	
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
	 * @Assert\NotBlank(message="Name should not be blank")
     * @Assert\MinLength(limit=3, message="Name must have at least {{ limit }} characters")
     * @Assert\MaxLength(limit=255, message="Name is too long, It should have {{ limit }} characters or less")
     */
    private $name;

    /**
     * @var string $extendedName
     *
     * @ORM\Column(name="extended_name", type="text", nullable=true)
     */
    private $extendedName;

    /**
     * @var string $inn
     *
     * @ORM\Column(name="inn", type="string", length=255)
	 * @Assert\NotBlank(message="'ИНН' should not be blank")
	 * @Assert\Type(type="numeric", message="'ИНН' '{{ value }}' is not a valid {{ type }}.")
     * @Assert\MinLength(limit=10, message="'ИНН' must have at least {{ limit }} characters")
     * @Assert\MaxLength(limit=12, message="'ИНН' is too long, It should have {{ limit }} characters or less")
     */
    private $inn;

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
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set extendedName
     *
     * @param text $extendedName
     */
    public function setExtendedName($extendedName)
    {
        $this->extendedName = $extendedName;
    }

    /**
     * Get extendedName
     *
     * @return text 
     */
    public function getExtendedName()
    {
        return $this->extendedName;
    }

    /**
     * Set inn
     *
     * @param string $inn
     */
    public function setInn($inn)
    {
        $this->inn = $inn;
    }

    /**
     * Get inn
     *
     * @return string 
     */
    public function getInn()
    {
        return $this->inn;
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
     * Add products
     *
     * @param Supplier\SupplierBundle\Entity\Product $products
     */
    public function addProduct(\Supplier\SupplierBundle\Entity\Product $products)
    {
        $this->products[] = $products;
    }

    /**
     * Get products
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Add suppliers
     *
     * @param Supplier\SupplierBundle\Entity\Supplier $suppliers
     */
    public function addSupplier(\Supplier\SupplierBundle\Entity\Supplier $suppliers)
    {
        $this->suppliers[] = $suppliers;
    }

    /**
     * Get suppliers
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getSuppliers()
    {
        return $this->suppliers;
    }
}