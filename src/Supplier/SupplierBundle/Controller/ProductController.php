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
			throw $this->createNotFoundException('No product found for id '.$id);
		}
		
		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($product);
		$em->flush();
			
        return $this->redirect($this->generateUrl('product_list'));
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
				return $this->redirect($this->generateUrl('product_list'));
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
				return $this->redirect($this->generateUrl('product_list'));
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
		 if (isset($_POST['_method']) && $_POST['_method'] == 'PUT' && isset($_POST['model']))
		 {
			 $model = (array)json_decode($_POST['model']);
			 
			 if (isset($model['id']) && is_numeric($model['id']) && $id == $model['id'])
			 {
				$product = $this->getDoctrine()
								->getRepository('SupplierBundle:Product')
								->find($model['id']);
				
				if (!$product) {
					echo json_encode(array('success'=>0));
					die();
				}
				
				$validator = $this->get('validator');
    
				$product->setName($model['name']);
				$product->setUnit((int)$model['unit']);
				
				$errors = $validator->validate($product);
				
				if (count($errors) > 0) {
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();
						
					echo json_encode(array('success'=>0, 'errors'=>$errorMessage));
					die();
					
				} else {
					
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($product);
					$em->flush();
					
					$attr = array('name' => $product->getName(), 'unit' => $product->getUnit());
					
					echo json_encode($attr);
					//echo json_encode(array('success'=>1, 'id'=>$product->getId() ));
					die();
				
				}
			 }
			 
		 } else {
			 echo json_encode(array('success'=>0));
			die(); 
		 }
	 }
	 
	
	/**
	 * @Route("/product/{id}/delete", name="product_ajax_delete")
	 */
	public function ajaxdeleteAction($id)
	{
		$product = $this->getDoctrine()
					->getRepository('SupplierBundle:Product')
					->find($id);
					
		if (!$product) {
			echo 0;
			die();
		}
		
		if(0){
			$em = $this->getDoctrine()->getEntityManager();				
			$em->remove($product);
			$em->flush();
		}
		
		echo $id;
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

		 //$result = array('success' => $success, 'result' =>$products_array);
			
		 $response = new Response(json_encode($products_array), 200);
		 $response->headers->set('Content-Type', 'application/json');
		 $response->sendContent();
		 die(); 
	 }
	
	/**
	 * @Route("/product/create", name="product_ajax_create")
	 */
	 public function ajaxcreateAction()
	 { 
		 $products = $this->getDoctrine()->getRepository('SupplierBundle:Product')->findAll();
		 $products_array = array();
		
		 if ($products)
			foreach ($products AS $p)
				$products_array[] = array( 	'id' => $p->getId(),
											'name'=> $p->getName(), 
											'unit' => $p->getUnit(),
											);

		 //$result = array('success' => $success, 'result' =>$products_array);
			
		 $response = new Response(json_encode($products_array), 200);
		 $response->headers->set('Content-Type', 'application/json');
		 $response->sendContent();
		 die(); 
	 }
}
