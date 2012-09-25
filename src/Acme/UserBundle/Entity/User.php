<?php
// src/Acme/UserBundle/Entity/User.php
namespace Acme\UserBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Acme\UserBundle\Entity\User
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Acme\UserBundle\Entity\UserRepository")
 */
class User implements UserInterface
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
    private $username;

    /**
	 * @var string $salt
	 *
     * @ORM\Column(type="string", length=32)
     */
    private $salt;

    /**
     * @var string $email
     *
     * @ORM\Column(name="email", type="string", length=255)
	 * @Assert\Email( message = "The email '{{ value }}' is not a valid email.")
     */
    private $email;

    /**
     * @var string $fullname
     *
     * @ORM\Column(name="fullname", type="string", length=255)
	 * @Assert\NotBlank(message="Full Name should not be blank")
     */
    private $fullname;

    /**
     * @var string $password
     *
     * @ORM\Column(name="password", type="string", length=255)
	 * @Assert\NotBlank(message="Password should not be blank")
     */
    private $password;


    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users")
     *
     */
    private $groups;
	
	
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
    	
	///	var_dump($_SERVER); die();
		
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
}