<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
	 * @Route("/supplier/list" , name="supplier_list")
	 * @Route("/")
	 * @Template()
	 */
	public function listAction()
	{
		$repository = $this->getDoctrine()->getRepository('SupplierBundle:Supplier');
		$suppliers = $repository->findAll();
		
		return array( 'suppliers' => $suppliers);
	}
	
	
	
	/**
	 * @Route("/supplier/json", name="supplier_json")
	 */
	 public function jsonAction()
	 {
		 $suppliers = $this->getDoctrine()->getRepository('SupplierBundle:Supplier')->findAll();
		 $suppliers_array = array();

		 if ($suppliers)
		 {
			foreach ($suppliers AS $p)
				$suppliers_array[] = array( 	'id' => $p->getId(),
												'name'=> $p->getName(), 
											);
			 $response = new Response(json_encode($suppliers_array), 200);
			 $response->headers->set('Content-Type', 'application/json');
			 $response->sendContent();
			 die(); 
		 }
		 else
		 {
			$result = array('has_error' => 1, 'errors' => 'Suppliers are not found');
			$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die(); 
		 }
	 }

}
