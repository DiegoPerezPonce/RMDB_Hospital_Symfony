<?php

namespace App\Repository;

use App\Entity\Nurse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Nurse>
 */
class NurseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Nurse::class);
    }

    /**
     * @return Nurse[]
     */
    public function findFiltered(?string $name, ?string $specialty, ?string $location, ?string $availability): array
    {
        $qb = $this->createQueryBuilder('n');

        if ($name) {
            $qb->andWhere('n.name LIKE :name OR n.user LIKE :name')
               ->setParameter('name', '%' . $name . '%');
        }

        if ($specialty && $specialty !== 'All Specialties') {
            $qb->andWhere('n.specialty = :specialty')
               ->setParameter('specialty', $specialty);
        }

        if ($location) {
            $qb->andWhere('n.location LIKE :location')
               ->setParameter('location', '%' . $location . '%');
        }

        if ($availability && $availability !== 'Any') {
            $qb->andWhere('n.availability = :availability')
               ->setParameter('availability', $availability);
        }

        return $qb->getQuery()->getResult();
    }
}
