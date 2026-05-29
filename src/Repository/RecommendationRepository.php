<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Recommendation;
use App\Entity\Structure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recommendation>
 */
class RecommendationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recommendation::class);
    }

    /**
     * Compte les recommandations par statut (pour les compteurs de dashboard).
     * Retourne un tableau ['S0' => 3, 'S3' => 5, ...]
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.status AS status, COUNT(r.id) AS total')
            ->groupBy('r.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }
        return $counts;
    }

    /**
     * Recommandations affectées à une structure donnée (vue chef de structure).
     *
     * @return Recommendation[]
     */
    public function findByStructure(Structure $structure): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.assignedStructure = :struct')
            ->setParameter('struct', $structure)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recommandations à un statut donné.
     *
     * @return Recommendation[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    /**
     * Recommandations affectées à un agent donné (RG-12).
     *
     * @return Recommendation[]
     */
    public function findByAssignedAgent(\App\Entity\User $agent): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.assignedAgent = :agent')
            ->setParameter('agent', $agent)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recommandations correspondant à plusieurs statuts.
     *
     * @param string[] $statuses
     * @return Recommendation[]
     */
    public function findByStatuses(array $statuses): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}