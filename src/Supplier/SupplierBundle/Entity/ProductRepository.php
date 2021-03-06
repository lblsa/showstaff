<?php

namespace Supplier\SupplierBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ProductRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductRepository extends EntityRepository
{
	public function findOneByIdJoinedToCompany($pid, $cid)
	{
		$query = $this->getEntityManager()
			->createQuery('
				SELECT p, c FROM SupplierBundle:Product p
				JOIN p.company c
				WHERE p.id = :pid AND c.id = :cid'
			)->setParameters(array( 'pid'=> $pid, 'cid'=> $cid)	);

		try {
			return $query->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}
}
