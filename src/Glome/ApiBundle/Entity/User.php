<?php
namespace Glome\ApiBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;

/**
* @ORM\Table(name="gateway_user")
* @ORM\Entity(repositoryClass="Glome\ApiBundle\Entity\UserRepository")
*/
class User implements UserInterface, \Serializable
{
  /**
  * @ORM\Column(type="integer")
  * @ORM\Id
  * @ORM\GeneratedValue(strategy="AUTO")
  */
  private $id;

  /**
  * @ORM\Column(type="string", length=255, unique=true)
  */
  private $username;

  /**
  * @ORM\Column(type="string", length=255, unique=true)
  */
  private $email;

  /**
  * @ORM\Column(type="string", length=32, nullable=true)
  */
  private $salt;

  /**
  * @ORM\Column(type="string", length=255, nullable=true)
  */
  private $password;

  /**
  * @ORM\Column(name="is_active", type="boolean")
  */
  private $isActive;

  public function __construct()
  {
      $this->isActive = true;
      $this->salt = md5(uniqid(null, true));
  }

    public function setId($id){
        $this->id = $id;
    }

  public function getId(){
      return $this->id;
  }

  /**
  * @inheritDoc
  */
  public function getUsername()
  {
      return $this->username;
  }

  /**
  * @inheritDoc
  */
  public function setUsername($username)
  {
      $this->username = $username;
      $this->email = $username;
  }

  /**
  * @inheritDoc
  */
  public function getSalt()
  {
      return $this->salt;
  }

  public function setSalt($salt)
  {
      $this->salt = $salt;
  }

  /**
  * @inheritDoc
  */
  public function getPassword()
  {
      return $this->password;
  }

  public function setPassword($password)
  {
      $this->password = $password;
  }

  /**
  * @inheritDoc
  */
  public function getRoles()
  {
      return array('ROLE_USER');
  }

  /**
  * @inheritDoc
  */
  public function eraseCredentials()
  {
  }

  /**
  * @see \Serializable::serialize()
  */
  public function serialize()
  {
      return serialize(
          array(
              $this->id,
          )
      );
  }

  /**
  * @see \Serializable::unserialize()
  */
  public function unserialize($serialized)
  {
      list (
          $this->id,
          ) = unserialize($serialized);
  }
}

