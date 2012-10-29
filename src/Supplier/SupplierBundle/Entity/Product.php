<?php

namespace Supplier\SupplierBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Supplier\SupplierBundle\Entity\Product
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Supplier\SupplierBundle\Entity\ProductRepository")
 */
class Product
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Assert\Type(type="integer", message="Id '{{ value }}' is not a valid {{ type }}.")
     */
    private $id;

    /**
     * @var integer $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
	private $active = 1;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
	 * @Assert\NotBlank(message="Name should not be blank")
	 * @Assert\Type(type="string", message="Name '{{ value }}' is not a valid {{ type }}.")
     * @Assert\MinLength(3)
     * @Assert\MaxLength(255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Unit", inversedBy="products")
     * @ORM\JoinColumn(name="unit", referencedColumnName="id", onDelete="CASCADE")
     *
	 * @Assert\NotBlank()
     */
    private $unit;

    /**
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="products")
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
     * Set unit
     *
     * @param Supplier\SupplierBundle\Entity\Unit $unit
     */
    public function setUnit(\Supplier\SupplierBundle\Entity\Unit $unit)
    {
        $this->unit = $unit;
    }

    /**
     * Get unit
     *
     * @return Supplier\SupplierBundle\Entity\Unit 
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }
}