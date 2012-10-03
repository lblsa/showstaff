<?php
// src/Acme/UserBundle/Entity/User.php
namespace Acme\UserBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Supplier\SupplierBundle\Entity\Company;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Acme\UserBundle\Entity\User
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Acme\UserBundle\Entity\UserRepository")
 */
class User implements UserInterface, \Serializable
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
     * @ORM\ManyToOne(targetEntity="\Supplier\SupplierBundle\Entity\Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $company;
    
    /**
     * @var bigint $username
     *
     * @ORM\Column(name="username", type="bigint", length=14, unique=true)
	 * @Assert\NotBlank(message="Phonenumber(Username) should not be blank")
     */
    protected $username;

    /**
	 * @var string $salt
	 *
     * @ORM\Column(type="string", length=32)
     */
    protected $salt;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=255)
	 * @Assert\Email( message = "The email '{{ value }}' is not a valid email.")
     */
    protected $email;

    /**
     * @var string $fullname
     *
     * @ORM\Column(name="fullname", type="string", length=255)
	 * @Assert\NotBlank(message="Full Name should not be blank")
     */
    protected $fullname;

    /**
     * @var string $password
     *
     * @ORM\Column(name="password", type="string", length=255)
	 * @Assert\NotBlank(message="Password should not be blank")
     */
    protected $password;


    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users")
     *
     */
    protected $groups;

    /**
     * @ORM\ManyToMany(targetEntity="\Supplier\SupplierBundle\Entity\Restaurant", inversedBy="users")
     *
     */
    protected $restaurants;
	
	
    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

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
     * Set username
     *
     * @param bigint $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Get username
     *
     * @return bigint 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set fullname
     *
     * @param string $fullname
     */
    public function setFullname($fullname)
    {
        $this->fullname = $fullname;
    }

    /**
     * Get fullname
     *
     * @return string 
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
       // $this->password = $password;
        $this->password = $this->fullname;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        //return $this->password;
        return $this->fullname;
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return $this->salt;
    }


    /**
     * @inheritDoc
     */
    public function eraseCredentials()
	{
	}

    /**
     * @inheritDoc
     */
    public function equals(UserInterface $user)
    {
        return $this->username === $user->getUsername();
    }
	
    public function getRoles()
    {
        return $this->groups->toArray();
    }

    /**
     * Set salt
     *
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * Clean groups
     *
     * @param Acme\UserBundle\Entity\Group $groups
     */
    public function cleanGroup()
    {
        $this->groups = array();
    }

    /**
     * Clean Restaurant
     *
     * @param Supplier\SupplierBundle\Entity\Restaurant $restaurants
     */
    public function cleanRestaurant()
    {
        $this->restaurants = array();
    }

    /**
     * Add groups
     *
     * @param Acme\UserBundle\Entity\Group $groups
     */
    public function addGroup(\Acme\UserBundle\Entity\Group $groups)
    {
        $this->groups[] = $groups;
    }

    /**
     * Get groups
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getGroups()
    {
        return $this->groups;
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
    
    
    public function serialize()
    {
       return serialize($this->id);
    }
   
    public function unserialize($data)
    {
        $this->id = unserialize($data);
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
}
