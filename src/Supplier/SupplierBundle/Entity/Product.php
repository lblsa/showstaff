<?php

namespace Supplier\SupplierBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

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
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
	 * @Assert\NotBlank()
	 * @Assert\Type(type="string", message="Name '{{ value }}' is not a valid {{ type }}.")
     * @Assert\MinLength(3)
     * @Assert\MaxLength(255)
     */
    private $name;

    /**
     * @var integer $unit
     *
     * @ORM\Column(name="unit", type="integer", length=11)
	 * @Assert\NotBlank()
	 * @Assert\Type(type="numeric", message="Unit '{{ value }}' is not a valid {{ type }}.")
     * @Assert\Min(1)
     * @Assert\Max(10)
     */
    private $unit;

    /**
     * @ORM\ManyToOne(targetEntity="Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\Type(type="integer", message="Company '{{ value }}' is not a valid {{ type }}.")
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
     * Set unit
     *
     * @param integer $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * Get unit
     *
     * @return integer 
     */
    public function getUnit()
    {
        return $this->unit;
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
