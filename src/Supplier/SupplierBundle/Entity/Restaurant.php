<?php

namespace Supplier\SupplierBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Supplier\SupplierBundle\Entity\Restaurant
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Supplier\SupplierBundle\Entity\RestaurantRepository")
 */
class Restaurant
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
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255)
	 * @Assert\Type(type="string", message="Name '{{ value }}' is not a valid {{ type }}.")
     * @Assert\MinLength(3)
     * @Assert\MaxLength(255)
     */
    private $name;

    /**
     * @var string $address
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     * @Assert\MaxLength(255)
     */
    private $address;

    /**
     * @var string $director
     *
     * @ORM\Column(name="director", type="string", length=255, nullable=true)
     * @Assert\MaxLength(255)
     */
    private $director;

    /**
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="restaurants")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $company;


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
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string 
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set director
     *
     * @param string $director
     */
    public function setDirector($director)
    {
        $this->director = $director;
    }

    /**
     * Get director
     *
     * @return string 
     */
    public function getDirector()
    {
        return $this->director;
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