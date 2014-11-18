<?php
namespace Glome\ApiBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * TODO: description.
 */
class UserRepository extends EntityRepository {
    /**
     * TODO: Description.
     */
    public function loadByApiKey($api_key, $id) {
        $qb = $this->createQueryBuilder('u');

        $qb->select('partial u.{id,username,email,enabled,lastLogin,locked,expired,roles,settings,created,credentialsExpired}')
            ->leftJoin('u.companies', 'c')
            ->where('c.api_key = :api_key AND u.id = :id')
            ->setParameter('api_key', $api_key)
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * TODO: Description.
     */
    public function loadByApi($search = null, $page = 1, $limit = null, $api_key = null) {
        $qb = $this->createQueryBuilder('u');

        $qb->select('partial u.{id,username,email,enabled,lastLogin,locked,expired,roles,settings,created,credentialsExpired}')
            ->leftJoin('u.companies', 'c')
            ->where('u.enabled = 1');

        if ($search) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('u.username', ':search'), $qb->expr()->like('u.email', ':search')))
                ->setParameter('search', '%' . $search . '%');
        }

        if ($api_key) {
            $qb->andWhere('c.api_key = :api_key')
                ->setParameter('api_key', $api_key);
        }

        if ($limit) {
            $qb->setMaxResults($limit)
                ->setFirstResult(($page == 1 ? 0 : $page * $limit));
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * TODO: Description.
     */
    public function getByUsername($username) {
        $qb = $this->createQueryBuilder('u');

        $qb->select('partial u.{id,username,email,enabled,lastLogin,locked,expired,roles,settings,created,credentialsExpired}')
            ->where('u.username LIKE :username')
            ->setParameter('username', $username);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
