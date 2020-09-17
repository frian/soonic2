<?php

namespace App\Repository;

use App\Entity\Song;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Song|null find($id, $lockMode = null, $lockVersion = null)
 * @method Song|null findOneBy(array $criteria, array $orderBy = null)
 * @method Song[]    findAll()
 * @method Song[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SongRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Song::class);
    }

    public function findByKeyword($keyword) {
        return $this->createQueryBuilder('s')
            ->join('s.album', 'al')
            ->join('s.artist', 'ar')
            ->where('s.title like :keyword')
            ->orWhere('al.name like :keyword')
            ->orWhere('ar.name like :keyword')
            ->setParameter('keyword', '%'.$keyword.'%')
            ->orderBy('s.artist', 'ASC')
            ->addOrderBy('s.album', 'ASC')
            ->addOrderBy('s.title', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function findByArtistAndAlbum($artist, $album) {
        return $this->createQueryBuilder('s')
            ->join('s.artist', 'ar')
            ->join('s.album', 'al')
            ->where('ar.name = :artist')
            ->andWhere('al.name = :album')
            ->setParameter('artist', $artist)
            ->setParameter('album', $album)
            ->orderBy('s.trackNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function getRandom($number) {

        $maxId = $this->createQueryBuilder('s')
            ->select('MAX(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $this->createQueryBuilder('s');

        for ($i = 1; $i <= $number ; $i++) {
            $qb->orWhere("s.id = :num_".$i);
            $qb->setParameter("num_".$i, random_int(1, $maxId));
        }

        return $qb->getQuery()->getResult();
    }
}
