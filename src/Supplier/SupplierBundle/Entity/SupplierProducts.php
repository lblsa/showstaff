<?php

namespace Supplier\SupplierBundle\Entity;

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
     */
    private $price;

    /**
     * @var boolean $prime
     *
     * @ORM\Column(name="prime", type="boolean")
     */
    private $prime;

    /**
     * @var string $supplier_name
     *
     * @ORM\Column(name="supplier_name", type="string", length=255)
     */
    private $supplier_name;


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
     * Set supplier_name
     *
     * @param string $supplierName
     */
    public function setSupplierName($supplierName)
    {
        $this->supplier_name = $supplierName;
    }

    /**
     * Get supplier_name
     *
     * @return string 
     */
    public function getSupplierName()
    {
        return $this->supplier_name;
    }
}