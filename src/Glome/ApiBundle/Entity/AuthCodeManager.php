<?php
  namespace Glome\ApiBundle\Entity;

  use Doctrine\ORM\EntityManager;
  use FOS\OAuthServerBundle\Model\AuthCodeInterface;
  use FOS\OAuthServerBundle\Model\AuthCodeManager as BaseAuthCodeManager;

  use Glome\ApiBundle\Entity\User;
  use Glome\ApiBundle\Entity\UserRepository;

  class AuthCodeManager extends BaseAuthCodeManager
  {
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $class;

    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param string                      $class
     */
    public function __construct(EntityManager $em, $class)
    {
      $this->em = $em;
      $this->repository = $em->getRepository($class);
      $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
      return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function findAuthCodeBy(array $criteria)
    {
      return $this->repository->findOneBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function updateAuthCode(AuthCodeInterface $authCode)
    {
      // Extract core User from AuthCode
      $leUser = $authCode->getUser();

      // Check if a matching gateway user is found
      $userRepo = $this->em->getRepository('Glome\ApiBundle\Entity\User');
      $user = $userRepo->findOneBy(array('username' => $leUser->getUsername()));

      // If not found, store this
      if (!$user) {
        $user = new User();
        $user->setUsername($leUser->getUsername());
        $user->setSalt($leUser->getSalt());
        $user->setPassword($leUser->getPassword());
        $this->em->persist($user);
      }

      // Set bundle User to AuthCode
      $authCode->setUser($user);

      $this->em->persist($authCode);
      $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAuthCode(AuthCodeInterface $authCode)
    {
      $this->em->remove($authCode);
      $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExpired()
    {
      $qb = $this->repository->createQueryBuilder('a');
      $qb
        ->delete()
        ->where('a.expiresAt < ?1')
        ->setParameters(array(1 => time()));

      return $qb->getQuery()->execute();
    }

    public function persist(AuthCode $authCode) {
      parent::persist($authCode);
    }
  }
