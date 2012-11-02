<?php

namespace Supplier\SupplierBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Supplier\SupplierBundle\Entity\Order
 *
 * @ORM\Table(name="Orders")
 * @ORM\Entity(repositoryClass="Supplier\SupplierBundle\Entity\OrderRepository")
 */
class Order
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
     * @ORM\ManyToOne(targetEntity="Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $company;

    /**
     * @var string $date
     *
     * @ORM\Column(name="booking_date", type="string", length=10)
     * @Assert\Regex(pattern="/^\d\d\d\d-\d\d-\d\d$/")
     */
    private $date;

    /**
     * @var boolean $completed
     *
     * @ORM\Column(name="completed", type="boolean", nullable=true)
     */
    private $completed;


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
     * Set completed
     *
     * @param boolean $completed
     */
    public function setCompleted($completed)
    {
        $this->completed = $completed;
    }

    /**
     * Get completed
     *
     * @return boolean 
     */
    public function getCompleted()
    {
        return $this->completed;
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