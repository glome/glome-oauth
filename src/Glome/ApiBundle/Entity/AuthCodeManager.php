<?php
  namespace Glome\ApiBundle\Entity;

  use Doctrine\ORM\EntityManager;
  use FOS\OAuthServerBundle\Model\AuthCodeInterface;
  use FOS\OAuthServerBundle\Model\AuthCodeManager as BaseAuthCodeManager;

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
      $authCode->setUser(null);
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
  }
