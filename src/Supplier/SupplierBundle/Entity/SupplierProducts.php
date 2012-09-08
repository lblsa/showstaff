<?php

namespace Supplier\SupplierBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * Supplier\SupplierBundle\Entity\SupplierProducts
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Supplier\SupplierBundle\Entity\SupplierProductsRepository")
 */
class SupplierProducts
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
     * @var float $price
     *
     * @ORM\Column(name="price", type="float")
	 * @Assert\NotBlank(message="<br>Price should not be blank")
	 * @Assert\Type(type="numeric", message="<br>Price '{{ value }}' is not a valid {{ type }}")
     * @Assert\Min(limit=0, message="<br>Price should have {{ limit }} characters or more")
     * @Assert\Max(limit=100000, message="<br>Price should have {{ limit }} characters or less")
     */
    private $price;

    /**
     * @var boolean $prime
     *
     * @ORM\Column(name="primary_supplier", type="boolean")
     */
    private $prime;

    /**
     * @var string $supplierName
     *
     * @ORM\Column(name="supplier_name", type="string", length=255)
	 * @Assert\NotBlank(message="<br>Name should not be blank")
	 * @Assert\Type(type="string", message="<br>Supplier Name '{{ value }}' is not a valid {{ type }}.")
     * @Assert\MinLength(limit=3, message="<br>Name must have at least {{ limit }} characters")
     * @Assert\MaxLength(limit=255, message="<br>Name is too long, It should have {{ limit }} characters or less")
     */
    private $supplierName;


    /**
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $product;

    /**
     * @ORM\ManyToOne(targetEntity="Supplier")
     * @ORM\JoinColumn(name="supplier_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $supplier;


    /**
     * @ORM\ManyToOne(targetEntity="Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $company;
    
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
     * Set price
     *
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * Get price
     *
     * @return float 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set prime
     *
     * @param boolean $prime
     */
    public function setPrime($prime)
    {
        $this->prime = $prime;
    }

    /**
     * Get prime
     *
     * @return boolean 
     */
    public function getPrime()
    {
        return $this->prime;
    }

    /**
     * Set supplierName
     *
     * @param string $supplierName
     */
    public function setSupplierName($supplierName)
    {
        $this->supplierName = $supplierName;
    }

    /**
     * Get supplierName
     *
     * @return string 
     */
    public function getSupplierName()
    {
        return $this->supplierName;
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
}
