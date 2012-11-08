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
     * @var integer $active
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
	private $active = 0;
	
    /**
     * @var bigint $username
     *
     * @ORM\Column(name="username", type="bigint", length=14, unique=true)
	 * @Assert\NotBlank(message="Номер телефона не может быть пустым")
	 * @Assert\MinLength(limit=4, message="Номер телефона должен содержать как минимум {{ limit }} символов")
	 * @Assert\MaxLength(limit=14, message="Номер телефона должен содержать как минимум {{ limit }} символов")
     */
    protected $username;

    /**
	 * @var string $salt
	 *
     * @ORM\Column(type="string", length=32)
     */
    protected $salt;
    
    /**
	 * @var string $activationCode
	 *
     * @ORM\Column(name="activation_code", type="string", length=32, nullable=true)
     */
    protected $activationCode;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=255, unique=true)
	 * @Assert\Email(message="Значение поля эл. почта '{{ value }}' некорректно.")
	 * @Assert\NotBlank(message="Необходимо указать адрес электронной почты")
     */
    protected $email;

    /**
     * @var string $fullname
     *
     * @ORM\Column(name="fullname", type="string", length=255)
	 * @Assert\NotBlank(message="ФИО не может быть пустым")
	 
     */
    protected $fullname;

    /**
     * @var string $password
     *
     * @ORM\Column(name="password", type="string", length=255)
	 * @Assert\NotBlank(message="Пароль не может быть пустым")
	 * @Assert\MinLength(limit=6, message="Пароль должен содержать как минимум {{ limit }} символов")
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
     * Clean roles
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function cleanRoles()
    {
        return $this->roles = new ArrayCollection();
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

    /**
     * Set activationCode
     *
     * @param string $activationCode
     */
    public function setActivationCode($activationCode)
    {
        $this->activationCode = $activationCode;
    }

    /**
     * Get activation_code
     *
     * @return string 
     */
    public function getActivationCode()
    {
        return $this->activationCode;
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
