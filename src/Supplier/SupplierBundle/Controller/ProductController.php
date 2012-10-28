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
	 * @Route(	"units", name="units", requirements={"_method" = "GET"})
	 * @Template()
	 */
	public function unitsAction()
	{
		$units = $this->getDoctrine()
						->getRepository('SupplierBundle:Unit')
						->findAll();
		if ($units)
		{
			foreach ($units AS $p)
				$units_array[] = array('id' => $p->getId(), 'name'=> $p->getName());
				
			$code = 200;
			$result = array('code' => $code, 'data' => $units_array);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
		else
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company units');
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
	}	
	
	/**
	 * @Route(	"company/{cid}/product", name="product", requirements={"_method" = "GET"})
	 * @Template()
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_ADMIN")
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
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden Company');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
				}
			}
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findAllProductsByCompany($cid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			else
			{
				throw $this->createNotFoundException('No company found for id '.$cid);
			}
		}

		$products = $company->getProducts();
			
		$products_array = array();
		
		if ($products)
		{
			foreach ($products AS $p)
			{
				if ($p->getActive())
				{
					$supplier_products = $this->getDoctrine()
										->getRepository('SupplierBundle:SupplierProducts')
										->findBy(
											array('company'=>$cid, 'product'=>$p->getId()), 
											array('prime'=>'DESC','price' => 'ASC'),
											1 ); // Сортируем по первичным, потом по цене с лимитом 1. Первый и будет тем, что надо.
					$price = 0;
					$supplier_product = 0;
					if ($supplier_products)
					{
						foreach ($supplier_products AS $sp)
						{
							if ($sp->getActive())
							{
								$price = $sp->getPrice();
								$supplier_product = $sp->getId();
							}
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
			
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $products_array);

			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
			header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");// HTTP/1.0
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
		
		return array('company' => $company);
	}
	
	/**
	 * @Route(	"company/{cid}/product/{pid}", name="product_ajax_update", requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	 public function ajaxupdateAction($cid, $pid, Request $request)
	 {
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden Company');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
				}
			}
		}
		
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $pid == $model['id'])
		{
			$product = $this->getDoctrine()
							->getRepository('SupplierBundle:Product')
							->find($model['id']);
			
			if (!$product)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No product found for id '.$pid);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			
			if (!$product->getActive())
			{
				$code = 403;
				$result = array('code'=>$code, 'message'=>'Запрещено редактировать (неактивный продукт)');
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));	
			}
			
			$validator = $this->get('validator');

			$unit = $this->getDoctrine()->getRepository('SupplierBundle:Unit')->find((int)$model['unit']);
			if (!$unit)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No unit found for id '.(int)$model['unit']);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			
			$product->setName($model['name']);
			$product->setUnit($unit);
			
			$errors = $validator->validate($product);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
				
				$code = 400;
				$result = array('code'=>$code, 'message'=>$errorMessage);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($product);
				$em->flush();
				
				$code = 200;
				
				$result = array('code'=> $code, 'data' => array(
																'name' => $product->getName(), 
																'unit' => $product->getUnit()->getId()
															));
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			
			}
		}
			
		$code = 400;
		$result = array('code'=> $code, 'message' => 'Invalid request');
		return Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		 
	 }
	 
	
	/**
	 * @Route(	"company/{cid}/product/{pid}", name="product_ajax_delete", requirements={"_method" = "DELETE"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
	 */
	public function ajaxdeleteAction($cid, $pid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden Company');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
				}
			}
		}
		
		$product = $this->getDoctrine()
					->getRepository('SupplierBundle:Product')
					->find($pid);
					
		if (!$product)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No product found for id '.$pid);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
		
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
		
		$code = 200;
		$result = array('code' => $code, 'data' => $pid);
		return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
	}
	

	/**
	 * @Route(	"company/{cid}/product", name="product_ajax_create", requirements={"_method" = "POST"})
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
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden Company');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
				}
			}
		}
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
						
		if (!$company) {
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}

		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['unit']) && isset($model['name']))
		{	
			$unit = $this->getDoctrine()->getRepository('SupplierBundle:Unit')->find((int)$model['unit']);
			
			if (!$unit) {
				$code = 404;
				$result = array('code' => $code, 'message' => 'No unit found for id '.(int)$model['unit']);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
		
			$validator = $this->get('validator');
			$product = new Product();
			$product->setName($model['name']);			
			$product->setUnit($unit);
			
			$errors = $validator->validate($product);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
					
				$code = 400;
				$result = array('code' => $code, 'message'=>$errorMessage);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				
			} else {
				
				$product->setCompany($company);
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($product);
				$em->flush();
				
				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $product->getId(),
																		'name' => $product->getName(), 
																		'unit' => $product->getUnit()->getId(),
																	));
				
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));			
			}
		}
		
		$code = 400;
		$result = array('code' => $code, 'message'=> 'Invalid request');
		return Response(json_encode($result), $code, array('Content-Type' => 'application/json'));

	}
}
