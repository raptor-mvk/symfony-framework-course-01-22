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

        return $qb->getQuery()->enableResultCache(null, "tweets_{$page}_{$perPage}")->getResult();
    }

    /**
     * @param int[] $authorIds
     *
     * @return Tweet[]
     */
    public function getByAuthorIds(array $authorIds, int $count): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('t')
            ->from($this->getClassName(), 't')
            ->where($qb->expr()->in('IDENTITY(t.author)', ':authorIds'))
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($count);

        $qb->setParameter('authorIds', $authorIds);

        return $qb->getQuery()->getResult();
    }
}
