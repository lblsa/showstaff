<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\SupplierType;
use JMS\SecurityExtraBundle\Annotation\Secure;

class SupplierController extends Controller
{
	/**
	 * @Route(	"api/company/{cid}/supplier.{_format}",
	 *			name="API_supplier",
	 *			requirements={"_method" = "GET", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Template()
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function API_listAction($cid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
					return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
				else
					throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findAllSupplierByCompany($cid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
				return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
			else
				throw $this->createNotFoundException('No company found for id '.$cid);
		}
		
		$suppliers = $company->getSuppliers();
		$suppliers_array = array();
		
		if ($suppliers)
		{
			foreach ($suppliers AS $p)
			{
				if($p->getActive())
				{
					$suppliers_array[] = array( 'id' => $p->getId(),
												'name'=> $p->getName());
				}
			}
		}
			
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0

		$result = array('code' => 200, 'data' => $suppliers_array);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	/**
	 * @Route("company/{cid}/supplier", name="supplier", requirements={"_method" = "GET"})
	 * @Template()
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function listAction($cid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
					return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
				else
					throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findAllSupplierByCompany($cid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
				return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
			else
				throw $this->createNotFoundException('No company found for id '.$cid);
		}
		
		$suppliers = $company->getSuppliers();
		$suppliers_array = array();
		
		if ($suppliers)
		{
			foreach ($suppliers AS $p)
			{
				if($p->getActive())
				{
					$suppliers_array[] = array( 'id' => $p->getId(),
												'name'=> $p->getName());
				}
			}
		}
			
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
		
		return array(	'company' => $company	);
	}

	/**
	 * @Route(	"api/company/{cid}/supplier/{sid}.{_format}", 
	 * 			name="API_supplier_update", 
	 * 			requirements={"_method" = "PUT", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	 public function API_updateAction($cid, $sid, Request $request)
	 {
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest())
					return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
				else
					throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
			 
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $sid == $model['id'])
		{
			$supplier = $this->getDoctrine()
							->getRepository('SupplierBundle:Supplier')
							->find($model['id']);
			
			if (!$supplier)
				return new Response('No supplier found for id '.$sid, 404, array('Content-Type' => 'application/json'));
			
			$validator = $this->get('validator');

			$supplier->setName($model['name']);
			
			$errors = $validator->validate($supplier);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();

				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
								
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier);
				$em->flush();
				
				$result = array('code'=> 200, 'data' => array('name' => $supplier->getName()));
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			
			}
		}

		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	 }
	 
	/**
	 * @Route(	"api/company/{cid}/supplier/{sid}.{_format}",
	 *			name="API_supplier_delete",
	 *			requirements={"_method" = "DELETE", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function API_deleteAction($cid, $sid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest())
					return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
				else
					throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$supplier = $this->getDoctrine()
					->getRepository('SupplierBundle:Supplier')
					->find($sid);
					
		if (!$supplier)
			return new Response('Не найден поставщик', 404, array('Content-Type' => 'application/json'));		
		
		$supplier->setActive(0);
		$em = $this->getDoctrine()->getEntityManager();				
		$em->persist($supplier);
		$em->flush();
		
		$q = $this->getDoctrine()
				->getRepository('SupplierBundle:SupplierProducts')
				->createQueryBuilder('p')
				->update('SupplierBundle:SupplierProducts p')
				->set('p.active', 0)
				->where('p.supplier = :supplier')
				->setParameters(array('supplier' => $supplier->getId()))
				->getQuery()
				->execute();
		
		// удалим все существующие заказы на сегодня и будущее
		$q = $this->getDoctrine()
				->getRepository('SupplierBundle:OrderItem')
				->createQueryBuilder('p')
				->delete('SupplierBundle:OrderItem p')
				->where('p.supplier = :supplier AND p.date >= :date')
				->setParameters(array('supplier' => $supplier->getId(), 'date' => date('Y-m-d')))
				->getQuery()
				->execute();
		
		$result = array('code' => 200, 'data' => $sid);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	
	/**
	 * @Route(	"api/company/{cid}/supplier.{_format}", 
	 * 			name="API_supplier_create", 
	 * 			requirements={"_method" = "POST", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function API_createAction($cid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
					return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
				else
					throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($cid);
						
		if (!$company)
			return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
		
		$model = (array)json_decode($request->getContent());

		
		if (count($model) > 0 && isset($model['name']))
		{
			$supplier = $this->getDoctrine()
							->getRepository('SupplierBundle:Supplier')
							->findOneByName($model['name']);

			if ($supplier)
			{
				$supplier->setActive(1);
			}
			else
			{
				$validator = $this->get('validator');
				$supplier = new Supplier();
				$supplier->setName($model['name']);
				
				$errors = $validator->validate($supplier);
				
				if (count($errors) > 0) {
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();

					return new Response(implode(', ', $errorMessage), 400, array('Content-Type' => 'application/json'));
					
				} else {					
					$supplier->setCompany($company);
				}
			}
			
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($supplier);
			$em->flush();

			$result = array('code' => 200, 'data' => array(	'id' => $supplier->getId(),
															'name' => $supplier->getName()
															));
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));

		}
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}
}
