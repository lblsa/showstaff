<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\ProductType;

class ProductController extends Controller
{
	public $unit = array(	'1' => 'кг',
							'2' => 'литр',
							'3' => 'шт',
							'4' => 'пучок',
							'5' => 'бутылка',);
	
    /**
     * @Route("/product/del/{id}", name="product_del")
     */
    public function delAction($id)
    {
		$product = $this->getDoctrine()
						->getRepository('SupplierBundle:Product')
						->find($id);
						
		if (!$product) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'errors' => 'No product found for id '.$id);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No product found for id '.$id);
			}
		}
		
		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($product);
		$em->flush();
		
		if ($request->isXmlHttpRequest()) 
		{
			$result = array('has_error' => 0, 'result' => 'Product #'.$id.' is deleted');
			$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		else
		{
			return $this->redirect($this->generateUrl('product_list'));
		}
    }
    

    /**
     * @Route("/product/create", name="product_create")
     * @Template()
     */    
    public function createAction(Request $request)
    {
		$product = new Product();
		
		$form = $this->createForm(new ProductType($this->unit), $product);
					
		if ($request->getMethod() == 'POST')
		{			
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$product = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($product);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$result = array('has_error' => 0, 'result' => 'Product #'.$product->getId().' is created');
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('product_list'));
				}
			}
		}


		return array('form' => $form->createView());
	}
	
	
    /**
     * @Route("/product/edit/{id}", name="product_edit")
     * @Template()
     */    
	public function editAction($id, Request $request)
	{
		$product = $this->getDoctrine()
						->getRepository('SupplierBundle:Product')
						->find($id);
		
		if (!$product) {
			throw $this->createNotFoundException('No product found for id '.$id);
		}
		
		$form = $this->createForm(new ProductType($this->unit), $product);
					
		if ($request->getMethod() == 'POST')
		{
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$product = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($product);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$result = array('has_error' => 0, 'result' => 'Product #'.$id.' is updated');
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('product_list'));
				}
			}
		}


		return array('form' => $form->createView(), 'product' => $product);
	}
	
	
	/**
	 * @Route("/product/list", name="product_list")
	 * @Template()
	 */
	public function listAction()
	{		
		$products = $this->getDoctrine()->getRepository('SupplierBundle:Product')->findAll();
		return array( 'products' => $products, 'unit' => $this->unit);
	}
	
	/**
	 * @Route("/product/{id}/update", name="product_ajax_update")
	 */
	 public function ajaxupdateAction($id)
	 {
		 if (isset($_POST['model'])) 
		 {
			 $model = (array)json_decode($_POST['model']);
			 
			 if (isset($model['id']) && is_numeric($model['id']) && $id == $model['id'])
			 {
				$product = $this->getDoctrine()
								->getRepository('SupplierBundle:Product')
								->find($model['id']);
				
				if (!$product)
				{
					$result = array('has_error' => 1, 'errors' => 'No product found for id '.$id);
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				
				$validator = $this->get('validator');
    
				$product->setName($model['name']);
				$product->setUnit((int)$model['unit']);
				
				$errors = $validator->validate($product);
				
				if (count($errors) > 0) {
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();
						
					$result = array('has_error'=>1, 'errors'=>$errorMessage);
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
					
				} else {
					
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($product);
					$em->flush();
					
					$result = array('has_error'=> 0, 'name' => $product->getName(), 'unit' => $product->getUnit());
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				
				}
			} 
		}
		 
		$result = array('has_error'=> 1, 'errors' => 'Invalid request');
		$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
		 
	 }
	 
	
	/**
	 * @Route("/product/{id}/delete", name="product_ajax_delete")
	 */
	public function ajaxdeleteAction($id)
	{
		$product = $this->getDoctrine()
					->getRepository('SupplierBundle:Product')
					->find($id);
		if (!$product)
		{
			$result = array('has_error' => 1, 'errors' => 'No product found for id '.$id);
			$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($product);
		$em->flush();
		
		$result = array('has_error' => 0, 'result' => $id);
		$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	}
	

	/**
	 * @Route("/product/create/ajax", name="product_ajax_create")
	 */
	 public function ajaxcreateAction()
	 {
		 if (isset($_POST['model']))
		 {
			 $model = (array)json_decode($_POST['model']);
			 
			 if (isset($model['name']))
			 {
				$validator = $this->get('validator');
				$product = new Product();
				$product->setName($model['name']);
				$product->setUnit((int)$model['unit']);
				
				$errors = $validator->validate($product);
				
				if (count($errors) > 0) {
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();
						
					$result = array('has_error'=>1, 'errors'=>$errorMessage);
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
					
				} else {
					
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($product);
					$em->flush();
					
					$result = array('has_error'=> 0, 'id' => $product->getId(), 'name' => $product->getName(), 'unit' => $product->getUnit());
					
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				
				}
			}
		}
		
		$result = array('has_error'=>1, 'errors'=> 'Invalid request');
		$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	 
	 }

	
	
	/**
	 * @Route("/product/json", name="product_json")
	 */
	 public function jsonAction()
	 { 
		 $products = $this->getDoctrine()->getRepository('SupplierBundle:Product')->findAll();
		 $products_array = array();
		 if ($products)
			foreach ($products AS $p)
				$products_array[] = array( 	'id' => $p->getId(),
											'name'=> $p->getName(), 
											'unit' => $p->getUnit(),
											);
			
		 $response = new Response(json_encode($products_array), 200);
		 $response->headers->set('Content-Type', 'application/json');
		 $response->sendContent();
		 die(); 
	 }
}
