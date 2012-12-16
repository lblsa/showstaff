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
use Supplier\SupplierBundle\Form\Type\ProductType;
use JMS\SecurityExtraBundle\Annotation\Secure;

class ProductController extends Controller
{
	/**
	 * @Route(	"api/units.{_format}",
	 *			name="API_units", 
	 *			requirements={"_method" = "GET", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})	 
	 * @Template()
	 */
	public function API_unitsAction()
	{
		$units = $this->getDoctrine()
						->getRepository('SupplierBundle:Unit')
						->findAll();
		if ($units)
		{
			foreach ($units AS $p)
				$units_array[] = array('id' => $p->getId(), 'name'=> $p->getName());
				
			$result = array('code' => 200, 'data' => $units_array);
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
		}
		else
			return new Response('Not found units', 404, array('Content-Type' => 'application/json'));
	}	
	
	/**
	 * @Route(	"company/{cid}/product", name="product", requirements={"_method" = "GET"})
	 * @Template()
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_ADMIN")
	 */
	public function listAction($cid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		$restaurants_list = array();

		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllProductsByCompany($cid);
		if (!$company)
			throw $this->createNotFoundException('No company found for id '.$cid);

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				throw new AccessDeniedHttpException('Нет доступа к компании');
		
		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);

		if ($restaurants)
			foreach ($restaurants as $r)
				$restaurants_list[$r->getId()] = $r->getName();
	

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0	
		return array('company' => $company, 'restaurants_list' => $restaurants_list );
	}
	
	/**
	 * @Route(	"api/company/{cid}/product.{_format}", 
	 *			name="API_product", 
	 *			requirements={"_method" = "GET", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})	 
	 * @Template()
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_ADMIN")
	 */
	public function API_listAction($cid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
	
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllProductsByCompany($cid);
		if (!$company)
			return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		$products = $company->getProducts();
			
		$products_array = array();
		
		$suppliers = $this->getDoctrine()
							->getRepository('SupplierBundle:Supplier')
							->findBy(array('company'=>(int)$cid, 'active' =>1));
							
		$suppliers_array = array();
		foreach($suppliers AS $supplier)
			$suppliers_array[] = $supplier->getId();
						
		if ($products)
		{
			foreach ($products AS $p)
			{
				if ($p->getActive())
				{			
					$best_supplier_offer = $this->getDoctrine()
											->getRepository('SupplierBundle:SupplierProducts')
											->getBestOffer((int)$cid, (int)$p->getId(), $suppliers_array);
					

					$price = 0;
					$supplier_product = 0;
					if ($best_supplier_offer)
					{
						if ($best_supplier_offer->getActive() && $best_supplier_offer->getSupplier()->getActive())
						{
							$price = $best_supplier_offer->getPrice();
							$supplier_product = $best_supplier_offer->getId();
						}
					}
				
					$products_array[] = array( 	'id' => $p->getId(),
												'name'=> $p->getName(), 
												'unit' => $p->getUnit()->getId(),
												'use'	=> 0,
												'price'	=> $price,
												'supplier_product'	=> $supplier_product );
				}
			}
		}
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
		
		$result = array('data' => $products_array, 'code' => 200);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	
	/**
	 * @Route(	"api/company/{cid}/product/{pid}.{_format}", 
	 *			name="API_product_update", 
	 *			requirements={"_method" = "PUT", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})	 
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	 public function API_updateAction($cid, $pid, Request $request)
	 {
		$user = $this->get('security.context')->getToken()->getUser();
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $pid == $model['id'])
		{
			$product = $this->getDoctrine()
							->getRepository('SupplierBundle:Product')
							->find($model['id']);
			
			if (!$product)
				return new Response('No product found for id '.$pid, 404, array('Content-Type' => 'application/json'));
			
			if (!isset($model['active']) && !$product->getActive())
				return new Response('Запрещено редактировать (неактивный продукт)', 403, array('Content-Type' => 'application/json'));
			
			$validator = $this->get('validator');

			$unit = $this->getDoctrine()->getRepository('SupplierBundle:Unit')->find((int)$model['unit']);
			
			if (!$unit)
				return new Response('No unit found for id '.(int)$model['unit'], 404, array('Content-Type' => 'application/json'));
			
			$other_product = $this->getDoctrine()->getRepository('SupplierBundle:Product')->findOneBy(array(	'name'		=> $model['name'],
																												'unit'		=> (int)$model['unit'],
																												'company'	=> $cid  ));
			
			if ($other_product && $other_product->getName() == $model['name'] && (int)$model['unit'] == $other_product->getUnit()->getId() && $other_product->getActive())
				return new Response('Такой продукт у вас уже существует', 400, array('Content-Type' => 'application/json'));
				
			$product->setName($model['name']);
			$product->setUnit($unit);
			$product->setActive(1);
			
			$errors = $validator->validate($product);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
					
				return new Response(implode(', ', $errorMessage), 400, array('Content-Type' => 'application/json'));
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($product);
				$em->flush();
				
				$result = array('code'=> 200, 'data' => array(
																'name' => $product->getName(), 
																'unit' => $product->getUnit()->getId()
															));
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			
			}
		}

		return Response('Некорректный запрос', 400, array('Content-Type' => 'application/json'));		 
	 }
	 
	
	/**
	 * @Route(	"api/company/{cid}/product/{pid}.{_format}", 
	 *			name="API_product_delete", 
	 *			requirements={"_method" = "DELETE", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})	 
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function API_deleteAction($cid, $pid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
		$product = $this->getDoctrine()->getRepository('SupplierBundle:Product')->find($pid);
					
		if (!$product)
			return new Response('No product found for id '.$pid, 404, array('Content-Type' => 'application/json'));
		
		$product->setActive(0);
		$em = $this->getDoctrine()->getEntityManager();				
		$em->persist($product);
		$em->flush();
		
		$q = $this->getDoctrine()
				->getRepository('SupplierBundle:SupplierProducts')
				->createQueryBuilder('p')
				->update('SupplierBundle:SupplierProducts p')
				->set('p.active', 0)
				->where('p.product = :product')
				->setParameters(array('product' => $product->getId()))
				->getQuery()
				->execute();
		
		$result = array('code' => 200, 'data' => $pid);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	

	/**
	 * @Route(	"api/company/{cid}/product.{_format}", 
	 *			name="API_product_create", 
	 *			requirements={"_method" = "POST", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})	 
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function API_createAction($cid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		if (!$company)
			return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['unit']) && isset($model['name']))
		{	
			$unit = $this->getDoctrine()->getRepository('SupplierBundle:Unit')->find((int)$model['unit']);
			
			if (!$unit)
				return new Response('No unit found for id '.(int)$model['unit'], 404, array('Content-Type' => 'application/json'));
		
			$product = $this->getDoctrine()->getRepository('SupplierBundle:Product')->findOneBy(array(	'name'		=> $model['name'],
																										'unit'		=> (int)$model['unit'],
																										'company'	=> $cid  ));
				
			if (!$product)
			{
				$validator = $this->get('validator');
				$product = new Product();
				$product->setName($model['name']);			
				$product->setUnit($unit);
				
				$errors = $validator->validate($product);
				
				if (count($errors) > 0) {
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();
						
					return new Response(implode(', ', $errorMessage), 400, array('Content-Type' => 'application/json'));
					
				} else {						
					$product->setCompany($company);
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($product);
					$em->flush();
					
					$result = array(	'code' => 200, 'data' => array(	'id' => $product->getId(),
																			'name' => $product->getName(), 
																			'unit' => $product->getUnit()->getId(),
																			'active' => 1
																		));
					
					return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));		
				}
			}
			else
			{
				if ($product->getActive())
					return new Response('Такой продукт у вас уже существует', 400, array('Content-Type' => 'application/json'));
				
				$product->setActive(1);
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($product);
				$em->flush();
				
				$result = array(	'code'=>200,
									'data'=> array( 'id'=>$product->getId(),
													'name' => $product->getName(), 
													'unit' => $product->getUnit()->getId(),
													'active' => 1 ) );
					
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		
		return Response('Некорректный запрос', 400, array('Content-Type' => 'application/json'));
	}
}
