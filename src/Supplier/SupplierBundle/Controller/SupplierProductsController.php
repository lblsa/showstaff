<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Supplier\SupplierBundle\Entity\SupplierProducts;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\SupplierProductsType;
use JMS\SecurityExtraBundle\Annotation\Secure;

class SupplierProductsController extends Controller
{
	/**
	 * @Route(	"/company/{cid}/supplier/{sid}/product", 
	 * 			name="supplier_products_list", 
	 * 			requirements={"_method" = "GET"})
	 * @Template()
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function listAction($cid, $sid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findOneCompanyOneSupplier($cid, $sid);

		if (!$company)
			throw $this->createNotFoundException('No supplier found for supplier_id='.$sid.' and company_id='.$cid );

		$supplier = $this->getDoctrine()
						->getRepository('SupplierBundle:Supplier')
						->find($sid);

		if (!$supplier)
			throw $this->createNotFoundException('No supplier found supplier_id='.$sid);

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0

		return array(	'company' => $company, 'supplier' => $supplier );
	}
	/**
	 * @Route(	"api/company/{cid}/supplier/{sid}/product.{_format}", 
	 * 			name="API_supplier_products_list", 
	 * 			requirements={"_method" = "GET", "_format" = "json|xml"},
				defaults={"_format" = "json"})	 
	 * @Template()
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function API_listAction($cid, $sid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
				return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findOneCompanyOneSupplier($cid, $sid);

		if (!$company)
		{
			if ($request->isXmlHttpRequest()) 
				return new Response('No supplier found for supplier_id='.$sid.' and company_id='.$cid, 404, array('Content-Type' => 'application/json'));
			else
				throw $this->createNotFoundException('No supplier found for supplier_id='.$sid.' and company_id='.$cid );
		}
		
		$supplier = $this->getDoctrine()->getRepository('SupplierBundle:Supplier')->find($sid);
		
		if (!$supplier)
			return new Response('No supplier found for supplier_id='.$sid, 404, array('Content-Type' => 'application/json'));
		
		if (!$supplier->getActive())
			return new Response('Запрещено редактирование. Поставщик неактивен', 403, array('Content-Type' => 'application/json'));

		
		$products = $company->getProducts();
			
		$products_array = array();
		
		if ($products)
		{
			foreach ($products AS $p)
				$products_array[$p->getId()] = array( 	'id' => $p->getId(),
														'name'	=> $p->getName(), 
														'unit'	=> $p->getUnit(), );
		}
		
		$supplier_products = $this->getDoctrine()
						->getRepository('SupplierBundle:SupplierProducts')
						->findBySupplier($sid);
		
		$supplier_products_array = array();
		
		foreach ($supplier_products AS $p)
		{	
			if ($p->getProduct()->getActive() && $p->getActive())
			{
				$supplier_products_array[] = array(	'id'					=>	$p->getId(),
													'price'					=>	$p->getPrice(),
													'product'				=>	$p->getProduct()->getId(),
													'primary_supplier'		=>	$p->getPrime(),
													//'supplier_product_name'	=>	$p->getSupplierName()?$p->getSupplierName():$p->getProduct()->getName(),
													'supplier_product_name'	=>	$p->getSupplierName(),
													);												
			}
		}
		$products_array = array_values($products_array);

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
			
		$result = array('code' => 200, 'data' => $supplier_products_array);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	 
	/**
	 * @Route(	"api/company/{cid}/supplier/{sid}/product/{pid}.{_format}",
	 * 			name="API_supplier_products_update", 
	 * 			requirements={"_method" = "PUT", "_format" = "json|xml"},
				defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function API_updateAction($cid, $sid, $pid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
				return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findOneCompanyOneSupplier($cid, $sid);
	
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
				return new Response('No supplier found for supplier_id='.$sid.' and company_id='.$cid, 404, array('Content-Type' => 'application/json'));
			else
				throw $this->createNotFoundException('No supplier found for supplier_id='.$sid.' and company_id='.$cid );
		}
		
		$products = $company->getProducts();
		foreach ($products AS $p)	$products_array[$p->getId()] = $p;
		
		$suppliers = $company->getSuppliers();
		foreach ($suppliers AS $s)	$supplier = $s;
		 
		$model = (array)json_decode($request->getContent());
		 
		if (isset($model) && isset($model['supplier_product_name']) && isset($model['price']))
		{
			$supplier_product = $this->getDoctrine()
					->getRepository('SupplierBundle:SupplierProducts')
					->find($pid);
			
			if (!$supplier_product)
				return new Response('No supplier product found for id '.$pid, 404, array('Content-Type' => 'application/json'));

			if (!$supplier_product->getActive())
				return new Response('Запрещено редактировать (неактивный продукт)', 403, array('Content-Type' => 'application/json'));

			
			if (!array_key_exists($model['product'], $products_array))
				return new Response('No product #'.(int)$model['product'].' found for supplier product', 400, array('Content-Type' => 'application/json'));
			
			$validator = $this->get('validator');
			
			$price = 0+$model['price'];
			if ($model['primary_supplier'] == 1)
			{
				$q = $this->getDoctrine()
						->getRepository('SupplierBundle:SupplierProducts')
						->createQueryBuilder('p')
						->update('SupplierBundle:SupplierProducts p')
				        ->set('p.prime', 0)
				        ->where('p.company = :company AND p.product = :product')
				        ->setParameters(array('company' => $company, 'product' => $products_array[(int)$model['product']]))
				        ->getQuery()
						->execute();
			}
			
			
			$supplier_product->setSupplierName($model['supplier_product_name']);
			$supplier_product->setPrime($model['primary_supplier']);
			$supplier_product->setPrice($price);
			$supplier_product->setSupplier($supplier);
			$supplier_product->setProduct($products_array[(int)$model['product']]);
			$supplier_product->setCompany($company);
			
			$errors = $validator->validate($supplier_product);
			
			if (count($errors) > 0) {
				
				foreach($errors['validate'] AS $error)
					$errorMessage[] = $error->getMessage();
				
				return new Response(implode(', ', $errorMessage), 400, array('Content-Type' => 'application/json'));
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier_product);
				$em->flush();
				
				$attr = array(	'id' => $supplier_product->getId(), 
								'supplier_product_name' => $supplier_product->getSupplierName(), 
								'price' => $supplier_product->getPrice(), 
								'supplier' => $supplier->getId(), 
								'primary_supplier' => $supplier_product->getPrime(), 
								'product' => $supplier_product->getProduct()->getId());

				$result = array('code'=>200, 'data'=> $attr);
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}

		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	 }
	 
	 
	/**
	 * @Route(	"api/company/{cid}/supplier/{sid}/product.{_format}", 
	 * 			name="API_supplier_products_create",
	 * 			requirements={"_method" = "POST", "_format" = "json|xml"},
				defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function API_createAction($cid, $sid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
				return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findOneCompanyOneSupplier($cid, $sid);

		if (!$company)
			return new Response('No supplier found for supplier_id='.$sid.' and company_id='.$cid, 404, array('Content-Type' => 'application/json'));
		
		$products = $company->getProducts();
		foreach ($products AS $p)	$products_array[$p->getId()] = $p;
		 
		$suppliers = $company->getSuppliers();
		foreach ($suppliers AS $s)	$supplier = $s;
		 
		$model = (array)json_decode($request->getContent());
		
		if (isset($model['supplier_product_name']) && isset($model['product']))
		{
			if (!array_key_exists($model['product'], $products_array))
				return new Response('No product #'.(int)$model['product'].' found for supplier product', 400, array('Content-Type' => 'application/json'));
	
			$price = 0+$model['price'];
			if ($model['primary_supplier'] == 1)
			{
				$q = $this->getDoctrine()
						->getRepository('SupplierBundle:SupplierProducts')
						->createQueryBuilder('p')
						->update('SupplierBundle:SupplierProducts p')
				        ->set('p.prime', 0)
				        ->where('p.company = :company AND p.product = :product')
				        ->setParameters(array('company' => $company, 'product' => $products_array[(int)$model['product']]))
				        ->getQuery()
						->execute();
			}
	
	
			$validator = $this->get('validator');
			$supplier_product = new SupplierProducts();
			$supplier_product->setSupplierName($model['supplier_product_name']);
			$supplier_product->setPrime($model['primary_supplier']);
			$supplier_product->setPrice($price);
			$supplier_product->setSupplier($supplier);
			$supplier_product->setCompany($company);
			$supplier_product->setProduct($products_array[(int)$model['product']]);
			
			$errors = $validator->validate($supplier_product);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();

				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier_product);
				$em->flush();
				
				$attr = array(	'id' => $supplier_product->getId(), 
								'supplier_product_name' => $supplier_product->getSupplierName(), 
								'price' => $supplier_product->getPrice(),
								'primary_supplier' => $supplier_product->getPrime(), 
								'product' => $supplier_product->getProduct()->getId());
				
				$result = array('code' => 200, 'data' => $attr);
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}	
	 
	 
	/**
	 * @Route(	"api/company/{cid}/supplier/{sid}/product/{pid}.{_format}", 
	 * 			name="API_supplier_products_delete", 
 	 * 			requirements={"_method" = "DELETE", "_format" = "json|xml"},
				defaults={"_format" = "json"})	
 	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function API_deleteAction($cid, $sid, $pid)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
				return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findOneCompanyOneSupplier($cid, $sid);
		
		if (!$company)
			return new Response('No supplier found for supplier_id='.$sid.' and company_id='.$cid, 404, array('Content-Type' => 'application/json'));

		 
		$supplier_product = $this->getDoctrine()
				->getRepository('SupplierBundle:SupplierProducts')
				->find($pid);
		
		if (!$supplier_product)
			return new Response('No supplier product found for id '.$pid, 404, array('Content-Type' => 'application/json'));

		$supplier_product->setActive(0);
		$em = $this->getDoctrine()->getEntityManager();				
		$em->persist($supplier_product);
		$em->flush();
	
		$result = array('code' => 200, 'data' => $pid);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	 }
}
