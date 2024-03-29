<?php

namespace App\Repository;

use App\Entity\Advice;
use App\Entity\Category;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Advice>
 *
 * @method Advice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Advice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Advice[]    findAll()
 * @method Advice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advice::class);
    }

    public function add(Advice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Advice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param int $status The status of the advices to return
     * @param int $category The category of the advices to return
     *
     * @return Advice[] Returns an array of advices objects ordered by descending date with a limit of 5 by default
     */
    public function findLatestByCategory(int $status = 1, int $category = null)
    {
        return $this->createQueryBuilder('ad')
            ->orderBy('ad.created_at', 'DESC')
            ->where('ad.status = :status')
            ->setParameter('status', $status)
            ->andWhere('ad.category = :category')
            ->setParameter('category', $category)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $limit The number of advices to return
     * @param int $status The status of the advices to return
     * @return Advice[] Returns an array of advices objects ordered by descending date with a limit of 5 by default
     */
    public function findForHome(int $limit = 5, int $status = 1)
    {
        return $this->createQueryBuilder('ad')
            ->orderBy('ad.created_at', 'DESC')
            ->where('ad.status = :status')
            ->setParameter('status', $status)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Advice[] Returns an array of advices objects ordered by descending date
     */
    public function findAllOrderByDate()
    {
        return $this->createQueryBuilder('ad')
            ->orderBy('ad.created_at', 'DESC')
            ->where('ad.status = 1 OR ad.status = 2')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Advice[] Returns an array of advices objects ordered by descending date
     */
    public function findAllWithFilter(
        ?string $sortType,
        ?string $sortOrder,
        ?string $title = null,
        ?string $content = null,
        ?int $status = null,
        ?User $user = null,
        ?Category $category = null,
        ?DateTimeImmutable $dateFrom = null,
        ?DateTimeImmutable $dateTo = null
    ) {
        $qb = $this->createQueryBuilder('ar');

        if ($title) {
            $qb->andWhere('ar.title LIKE :title')->setParameter('title', "%$title%");
        }

        if ($content) {
            $qb->andWhere('ar.content LIKE :content')->setParameter('content', "%$content%");
        }

        if ($status !== null) {
            $qb->andWhere('ar.status = :status')->setParameter('status', $status);
        }

        if ($user) {
            $qb->andWhere('ar.contributor = :contributor')->setParameter('contributor', $user);
        }

        if ($category) {
            $qb->andWhere('ar.category = :category')->setParameter('category', $category);
        }

        if ($dateFrom) {
            $qb->andWhere('ar.created_at >= :dateFrom')->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('ar.created_at <= :dateTo')->setParameter('dateTo', $dateTo);
        }

        $qb->orderBy('ar.' . $sortType, $sortOrder);
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Advice[] Returns an array of advices objects filtered by user, published or removed, and ordered by descending date
     */
    public function findAllByUser($contributor)
    {
        return $this->createQueryBuilder('ad')
            ->where('ad.contributor = :contributor')
            ->setParameter("contributor", $contributor)
            ->andWhere('ad.status = 1 OR ad.status = 2')
            ->orderBy('ad.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Available parameters: category, page, limit, offset, sorttype, order, search
    public function findAllWithParameters(
        ?int $category,
        ?int $status,
        int $limit,
        int $offset,
        string $sortType,
        string $order,
        ?string $search
    ) {
        $qb = $this->createQueryBuilder('ad');

        if ($category) {
            $qb->andWhere('ad.category = :category')
                ->setParameter('category', $category);
        }

        if ($search) {
            $qb->andWhere('ad.content LIKE :search')->setParameter('search', "%$search%");
        }

        if ($status) {
            $qb->andWhere('ad.status = :status')->setParameter('status', $status);
        }

        $qb->orderBy('ad.' . $sortType, $order);
        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Advice[] Returns an array of Advice objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Advice
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
