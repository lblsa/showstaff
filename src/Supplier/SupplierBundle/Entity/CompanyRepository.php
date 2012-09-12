<?php

namespace Supplier\SupplierBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * CompanyRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CompanyRepository extends EntityRepository
{
	public function findAllSupplierByCompany($cid)
	{
		$query = $this->getEntityManager()
			->createQuery('
				SELECT p, c FROM SupplierBundle:Company c
				LEFT JOIN c.suppliers p
				WHERE c.id = :cid'
			)->setParameter('cid', $cid);
		
		try {
			return $query->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}
	
	
	public function findAllProductsByCompany($cid)
	{
		$query = $this->getEntityManager()
			->createQuery('
				SELECT p, c FROM SupplierBundle:Company c
				LEFT JOIN c.products p
				WHERE c.id = :cid'
			)->setParameter('cid', $cid);
		
		try {
			return $query->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}
	
	
	public function findAllRestaurantsByCompany($cid)
	{
		$query = $this->getEntityManager()
			->createQuery('
				SELECT r, c FROM SupplierBundle:Company c
				LEFT JOIN c.restaurants r
				WHERE c.id = :cid'
			)->setParameter('cid', $cid);
		
		try {
			return $query->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $e) {
			return null;
		}
	}
}
