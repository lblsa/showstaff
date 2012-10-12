<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Supplier\SupplierBundle\Entity\SupplierProducts;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\SupplierProductsType;
use JMS\SecurityExtraBundle\Annotation\Secure;

class SupplierProductsController extends Controller
{
	public $unit = array(	'1' => 'кг',
							'2' => 'литр',
							'3' => 'шт',
							'4' => 'пучок',
							'5' => 'бутылка',);
	
    /**
     * @Route("/supplier/products/del/{id}", name="supplier_products_del")
     */
    public function delAction($id, Request $request)
    {
		$supplier_product = $this->getDoctrine()
						->getRepository('SupplierBundle:SupplierProducts')
						->find($id);
						
		if (!$supplier_product) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'errors' => 'No Supplier Products found for id '.$id);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No Supplier Products found for id '.$id);
			}
		}
		
		$em = $this->getDoctrine()->getEntityManager();					
		$em->remove($supplier_product);
		$em->flush();
		
		if ($request->isXmlHttpRequest()) 
		{
			$result = array('has_error' => 0, 'errors' => 'Supplier Products found for id #'.$id.' is deleted');
			$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		else
		{
			return $this->redirect($this->generateUrl('supplier_products_list'));
		}
    }
	
	 /**
     * @Route("/supplier/products/create", name="supplier_products_create")
     * @Template()
     */    
    public function createAction(Request $request)
    {
		$supplier_product = new SupplierProducts();
		
		$form = $this->createForm(new SupplierProductsType(), $supplier_product);
					
		if ($request->getMethod() == 'POST')
		{			
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$supplier_product = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier_product);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$result = array('has_error' => 1, 'errors' => 'Supplier Products #'.$supplier_product->getId.' is created');
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('supplier_products_list'));
				}
			}
		}


		return array('form' => $form->createView());
	}
	
	 /**
     * @Route("/supplier/products/edit/{id}", name="supplier_products_edit")
     * @Template()
     */    
    public function editAction($id, Request $request)
    {
		$supplier_product = $this->getDoctrine()
						->getRepository('SupplierBundle:SupplierProducts')
						->find($id);
						
		$form = $this->createForm(new SupplierProductsType(), $supplier_product);
					
		if ($request->getMethod() == 'POST')
		{			
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$supplier_product = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier_product);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$code = 200;
					$result = array('code' => $code, 'message' => 'Supplier Products #'.$id.' is updated');
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('supplier_products_list'));
				}
			}
		}


		return array('form' => $form->createView(), 'id' => $id);
	}
	
	/**
	 * @Route(	"/company/{cid}/supplier/{sid}/product", 
	 * 			name="supplier_products_list", 
	 * 			requirements={"_method" = "GET"})
	 * @Template()
	 * @Secure(roles="ROLE_ORDER_MANAGER")
	 */
	public function listAction($cid, $sid, Request $request)
    {
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findOneCompanyOneSupplier($cid, $sid);
	
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No supplier found for supplier_id='.$sid.' and company_id='.$cid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No supplier found for supplier_id='.$sid.' and company_id='.$cid );
			}
		}
		
		$suppliers = $company->getSuppliers();
		$products = $company->getProducts();
			
		$products_array = array();
		
		if ($products)
		{
			foreach ($products AS $p)
				$products_array[$p->getId()] = array( 	'id' => $p->getId(),
														'name'	=> $p->getName(), 
														'unit'	=> $p->getUnit(), );
		}
		
		foreach ($suppliers AS $s)
			$supplier = $s;
		
		$supplier_products = $this->getDoctrine()
						->getRepository('SupplierBundle:SupplierProducts')
						->findBySupplier($sid);
		
		$supplier_products_array = array();
		
		foreach ($supplier_products AS $p)
		{	
			$supplier_products_array[] = array(	'id'					=>	$p->getId(),
												'price'					=>	$p->getPrice(),
												'product'				=>	$p->getProduct()->getId(),
												'primary_supplier'		=>	$p->getPrime(),
												'supplier_product_name'	=>	$p->getSupplierName(),
												);												
		}
		$products_array = array_values($products_array);

		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $supplier_products_array);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die(); 
		}

		return array(	'supplier_products' => $supplier_products, 
						'company' => $company, 
						'supplier' => $supplier, 
						'supplier_products_json' => json_encode($supplier_products_array),
						'products_json' => json_encode($products_array), );

	}
	
	
	/**
	 * @Route("/supplier/products/json", name="supplier_products_list_json")
	 */
	 public function jsonAction()
	 {
		 $supplier_products = $this->getDoctrine()
					->getRepository('SupplierBundle:SupplierProducts')
					->get();
					
		 $products_array = array();

		if ($supplier_products)
			foreach ($supplier_products AS $p)
				$products_array[] = array(
										'id' => $p->getId(), 
										'supplier_product_name'=>$p->getSupplierName(), 
										'primary_supplier'=>$p->getPrime(), 
										'price'=>$p->getPrice(), 
										'product'=>$p->getProduct()->getId(), 
										'product_name'=>$p->getProduct()->getName(), 
										'supplier'=>$p->getSupplier()->getId(),
										'supplier_name'=>$p->getSupplier()->getName(),
										'unit' => $p->getProduct()->getUnit(),										
										);

		 $response = new Response(json_encode($products_array), 200);
		 $response->headers->set('Content-Type', 'application/json');
		 $response->sendContent();
		 die(); 
	 }
	 
	 
	/**
	 * @Route("/supplier_products/supplier/{id}", name="supplier_products_by_supplier")
	 */
	 public function productsBySupplierAction($id)
	 {
		$supplier_products = $this->getDoctrine()->getRepository('SupplierBundle:SupplierProducts')->findBy(array('supplier' => $id));
		$products_array = array();
		//sleep(1);
		if ($supplier_products)
			foreach ($supplier_products AS $p)
				$products_array[] = array(
										'id' => $p->getId(), 
										'supplier_product_name'=>$p->getSupplierName(), 
										'primary_supplier'=>$p->getPrime(), 
										'price'=>$p->getPrice(), 
										'product'=>$p->getProduct()->getId(),
										'supplier'=>$p->getSupplier()->getId(),
										);

		 $response = new Response(json_encode($products_array), 200);
		 $response->headers->set('Content-Type', 'application/json');
		 $response->sendContent();
		 die(); 
	 }
	 
	/**
	 * @Route(	"/company/{cid}/supplier/{sid}/product/{pid}",
	 * 			name="supplier_products_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_ORDER_MANAGER")
	 */
	 public function ajaxupdateAction($cid, $sid, $pid, Request $request)
	 {
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findOneCompanyOneSupplier($cid, $sid);
	
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No supplier found for supplier_id='.$sid.' and company_id='.$cid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No supplier found for supplier_id='.$sid.' and company_id='.$cid );
			}
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
			{
				$code = 404;
				$result = array('code'=>$code, 'message'=>'No supplier product found for id '.$pid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			if (!array_key_exists($model['product'], $products_array))
			{
				$code = 400;
				$result = array('code'=>$code, 'message'=>'No product #'.(int)$model['product'].' found for supplier product');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			
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
				
				$code = 400;
				$result = array('code'=>$code, 'message'=>$errors);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
				
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
				$code = 200;
				$result = array('code'=>$code, 'data'=> $attr);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			
			}
		}
			
		$code = 400;
		$result = array('code'=>$code, 'message'=> 'Invalid request');
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();

	 }
	 
	 
	/**
	 * @Route(	"/company/{cid}/supplier/{sid}/product", 
	 * 			name="supplier_products_ajax_create",
	 * 			requirements={"_method" = "POST"})
	 * @Secure(roles="ROLE_ORDER_MANAGER")
	 */
	 public function ajaxcreateAction($cid, $sid, Request $request)
	 {
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findOneCompanyOneSupplier($cid, $sid);
	
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No supplier found for supplier_id='.$sid.' and company_id='.$cid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No supplier found for supplier_id='.$sid.' and company_id='.$cid );
			}
		}
		
		$products = $company->getProducts();
		foreach ($products AS $p)	$products_array[$p->getId()] = $p;
		 
		$suppliers = $company->getSuppliers();
		foreach ($suppliers AS $s)	$supplier = $s;
		 
		$model = (array)json_decode($request->getContent());
		
		if (isset($model['supplier_product_name']) && isset($model['product']))
		{
			if (!array_key_exists($model['product'], $products_array))
			{
				$code = 400;
				$result = array('code'=>$code, 'message'=>'No product #'.(int)$model['product'].' found for supplier product');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
	
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
					
				$code = 400;
				$result = array('code'=>$code, 'message'=>$errorMessage);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier_product);
				$em->flush();
				
				$attr = array(	'id' => $supplier_product->getId(), 
								'supplier_product_name' => $supplier_product->getSupplierName(), 
								'price' => $supplier_product->getPrice(),
								'primary_supplier' => $supplier_product->getPrime(), 
								'product' => $supplier_product->getProduct()->getId());
				
				$code = 200;
				$result = array('code' => $code, 'data' => $attr);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			
			}
		}
		
		$code = 400;
		$result = array('code'=>$code, 'message'=> 'Invalid request');
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	}	
	 
	 
	/**
	 * @Route(	"/company/{cid}/supplier/{sid}/product/{pid}", 
	 * 			name="supplier_products_ajax_delete", 
 	 * 			requirements={"_method" = "DELETE"})
 	 * @Secure(roles="ROLE_ORDER_MANAGER")
	 */
	 public function ajaxdeleteAction($cid, $sid, $pid)
	 {
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findOneCompanyOneSupplier($cid, $sid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No supplier found for supplier_id='.$sid.' and company_id='.$cid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No supplier found for supplier_id='.$sid.' and company_id='.$cid );
			}
		}
		 
		 
		$supplier_product = $this->getDoctrine()
				->getRepository('SupplierBundle:SupplierProducts')
				->find($pid);
		
		if (!$supplier_product)
		{
			$code = 404;
			$result = array('code'=>$code, 'message'=>'No supplier product found for id '.$pid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($supplier_product);
		$em->flush();
	
		$code = 200;
		$result = array('code' => $code, 'data' => $pid);
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	 }
}
