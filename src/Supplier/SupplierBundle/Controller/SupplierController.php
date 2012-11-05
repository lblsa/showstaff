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
		
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $suppliers_array);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
		
		return array(	'company' => $company,
						'suppliers' => $suppliers,
						'suppliers_json' => json_encode($suppliers_array)	);
	}

	/**
	 * @Route(	"company/{cid}/supplier/{sid}", 
	 * 			name="supplier_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	 public function ajaxupdateAction($cid, $sid, Request $request)
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
				
				$code = 200;
				$result = array('code'=> $code, 'data' => array('name' => $supplier->getName()));
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			
			}
		}

		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	 }
	 
	/**
	 * @Route(	"company/{cid}/supplier/{sid}", name="supplier_ajax_delete", requirements={"_method" = "DELETE"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function ajaxdeleteAction($cid, $sid, Request $request)
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
			return new Response('No supplier found for id '.$sid, 404, array('Content-Type' => 'application/json'));
		
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
		
		$code = 200;
		$result = array('code' => $code, 'data' => $sid);
		return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
	}
	
	/**
	 * @Route(	"company/{cid}/supplier", 
	 * 			name="supplier_ajax_create", 
	 * 			requirements={"_method" = "POST"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function ajaxcreateAction($cid, Request $request)
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
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier);
				$em->flush();
				
				$code = 200;
				$result = array('code' => $code, 'data' => array(	'id' => $supplier->getId(),
																	'name' => $supplier->getName()
																));
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			
			}
		}

		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	 
	}
}
