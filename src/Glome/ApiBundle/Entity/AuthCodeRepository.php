<?php
  namespace Glome\ApiBundle\Entity;

  use Doctrine\ORM\EntityRepository;
  use Doctrine\ORM\Mapping;

  class AuthCodeRepository extends EntityRepository {
    public function __construct($em, Mapping\ClassMetadata $class)
    {
      parent::__construct($em, $class);
    }
  }
