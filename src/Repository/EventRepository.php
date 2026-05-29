<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Recommendation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Retourne l'historique d'une recommandation, du plus récent au plus ancien.
     *
     * @return Event[]
     */
    public function findByRecommendation(Recommendation $recommendation): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.recommendation = :reco')
            ->setParameter('reco', $recommendation)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
