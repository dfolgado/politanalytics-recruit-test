<?php

namespace App\Repository;

use App\Entity\MemberEuropeanParliament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MemberEuropeanParliament>
 *
 * @method MemberEuropeanParliament|null find($id, $lockMode = null, $lockVersion = null)
 * @method MemberEuropeanParliament|null findOneBy(array $criteria, array $orderBy = null)
 * @method MemberEuropeanParliament[]    findAll()
 * @method MemberEuropeanParliament[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MemberEuropeanParliamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MemberEuropeanParliament::class);
    }
}
