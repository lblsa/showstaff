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
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     *
     * @var ArrayCollection $roles
     */
    protected $roles;
	
	
    public function __construct()
    {
        $this->roles = new ArrayCollection();
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
       $this->password = $password;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
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
     * Clean roles
     *
     * @param Acme\UserBundle\Entity\Role $roles
     */
    public function cleanRoles()
    {
        $this->roles = array();
    }

    /**
     * Add role
     *
     * @param Acme\UserBundle\Entity\Role $role
     */
    public function addRole(\Acme\UserBundle\Entity\Role $role)
    {
        $this->roles[] = $role;
    }
    
	/**
	 * Gets an array of roles.
	 *
	 * @return array An array of Role objects
	 */	
    public function getRoles()
    {
        return $this->roles->toArray();
    }
    
    
    /**
     * Gets the user roles.
     *
     * @return ArrayCollection A Doctrine ArrayCollection
     */
    public function getUserRoles()
    {
        return $this->roles;
    }
    
    
    public function serialize()
    {
       return serialize($this->id);
    }
   
    public function unserialize($data)
    {
        $this->id = unserialize($data);
    }
}
