<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\SupplierType;

class SupplierController extends Controller
{
    /**
     * @Route("/supplier", name="supplier")
     * @Template()
     */    
    public function indexAction()
    {
		return array();
	}
	
	
    /**
     * @Route("/supplier/del/{id}", name="supplier_del")
     */
    public function delAction($id)
    {
		$supplier = $this->getDoctrine()
						->getRepository('SupplierBundle:Supplier')
						->find($id);
						
		if (!$supplier) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'errors' => 'No Supplier found for id '.$id);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No Supplier found for id '.$id);
			}
		}
		
		$em = $this->getDoctrine()->getEntityManager();		
		$em->remove($supplier);
		$em->flush();

		if ($request->isXmlHttpRequest()) 
		{
			$result = array('has_error' => 0, 'result' => 'Supplier #'.$id.' is deleted');
			$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		else
		{
			return $this->redirect($this->generateUrl('supplier_list'));
		}
    }
	
	
    /**
     * @Route("/supplier/create", name="supplier_create")
     * @Template()
     */    
    public function createAction(Request $request)
    {
		$supplier = new Supplier();
		
		$form = $this->createFormBuilder($supplier)
					->add('name', 'text')
					->getForm();
					
		if ($request->getMethod() == 'POST')
		{			
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$supplier = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$result = array('has_error' => 0, 'result' => 'Supplier #'.$supplier->getId().' is created');
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('supplier_list'));
				}
			}
		}


		return array('form' => $form->createView());
	}
	
	
    /**
     * @Route("/supplier/edit/{id}", name="supplier_edit")
     * @Template()
     */    
	public function editAction($id, Request $request)
	{
		$supplier = $this->getDoctrine()
						->getRepository('SupplierBundle:Supplier')
						->find($id);
		
		if (!$supplier) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'errors' => 'No Supplier found for id '.$id);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No Supplier found for id '.$id);
			}
		}
		
		$form = $this->createFormBuilder($supplier)
					->add('name', 'text')
					->getForm();
					
		if ($request->getMethod() == 'POST')
		{
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$supplier = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier);
				$em->flush();
				
				if ($request->isXmlHttpRequest())
				{
					$result = array('has_error' => 0, 'result' => 'Supplier #'.$id.' is updated');
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('supplier_list'));
				}
			}
		}


		return array('form' => $form->createView(), 'supplier' => $supplier);
	}
	

	/**
	 * @Route(	"company/{cid}/supplier", 
	 * 			name="supplier",
	 * 			requirements={"_method" = "GET"})
	 * 			
	 * @Template()
	 */
	public function listAction($cid, Request $request)
	{
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->findAllSupplierByCompany($cid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No company found for id '.$cid);
			}
		}
		
		$supplier = new Supplier();
		
		$form = $this->createForm(new SupplierType(), $supplier);
		
		if ($request->getMethod() == 'POST')
		{			
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$supplier = $form->getData();
				$supplier->setCompany($company);		
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$code = 200;
					$result = array('code' => $code, 'message' => 'Supplier #'.$supplier->getId().' is created');
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('supplier', array('cid' => $cid)));
				}
			}
		}
		
		$suppliers = $company->getSuppliers();
		$suppliers_array = array();
		
		if ($suppliers)
		{
			foreach ($suppliers AS $p)
				$suppliers_array[] = array( 'id' => $p->getId(),
											'name'=> $p->getName());
		}
			
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			
			$result = array('code' => $code, 'data' => $suppliers_array);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die(); 
		}
		
		return array('company' => $company, 'suppliers' => $suppliers, 'suppliers_json' => json_encode($suppliers_array), 'form' => $form->createView());
	}

	/**
	 * @Route(	"company/{cid}/supplier/{sid}", 
	 * 			name="supplier_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 */
	 public function ajaxupdateAction($cid, $sid, Request $request)
	 {		 
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $sid == $model['id'])
		{
			$supplier = $this->getDoctrine()
							->getRepository('SupplierBundle:Supplier')
							->find($model['id']);
			
			if (!$supplier)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No supplier found for id '.$sid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$validator = $this->get('validator');

			$supplier->setName($model['name']);
			
			$errors = $validator->validate($supplier);
			
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
				$em->persist($supplier);
				$em->flush();
				
				$code = 200;
				
				$result = array('code'=> $code, 'data' => array('name' => $supplier->getName()));
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			
			}
		}
			
		$code = 400;
		$result = array('code'=> $code, 'message' => 'Invalid request');
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
		 
	 }
	 
	/**
	 * @Route(	"company/{cid}/supplier/{sid}", 
	 * 			name="supplier_ajax_delete", 
	 * 			requirements={"_method" = "DELETE"})
	 */
	public function ajaxdeleteAction($cid, $sid, Request $request)
	{
		$supplier = $this->getDoctrine()
					->getRepository('SupplierBundle:Supplier')
					->find($sid);
					
		if (!$supplier)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No supplier found for id '.$sid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($supplier);
		$em->flush();
		
		$code = 200;
		$result = array('code' => $code, 'data' => $sid);
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	}
	
	/**
	 * @Route(	"company/{cid}/supplier", 
	 * 			name="supplier_ajax_create", 
	 * 			requirements={"_method" = "POST"})
	 */
	public function ajaxcreateAction($cid, Request $request)
	{
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($cid);
						
		if (!$company) {
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
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
					
				$code = 400;
				$result = array('code' => $code, 'message'=>$errorMessage);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
				
			} else {
				
				$supplier->setCompany($company);
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier);
				$em->flush();
				
				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $supplier->getId(),
																		'name' => $supplier->getName()
																	));
				
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			
			}
		}
		
		$code = 400;
		$result = array('code' => $code, 'message'=> 'Invalid request');
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	 
	}
}
