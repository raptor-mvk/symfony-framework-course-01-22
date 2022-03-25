<?php

namespace App\Repository;

use App\Entity\Tweet;
use Doctrine\ORM\EntityRepository;

class TweetRepository extends EntityRepository
{
    /**
     * @return Tweet[]
     */
    public function getTweets(int $page, int $perPage): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('t')
            ->from($this->getClassName(), 't')
            ->orderBy('t.id', 'DESC')
            ->setFirstResult($perPage * $page)
            ->setMaxResults($perPage);

        return $qb->getQuery()->getResult();
    }
}
