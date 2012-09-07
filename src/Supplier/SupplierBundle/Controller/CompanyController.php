<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Company;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\CompanyType;


class CompanyController extends Controller
{
	
	/**
	 * @Route("/company", name="company")
	 * @Template()
	 */
	public function listAction(Request $request)
	{		
		
		$company = new Company();
		
		$form = $this->createForm(new CompanyType(), $company);
		if ($request->getMethod() == 'POST')
		{
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$company = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($company);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$result = array('has_error' => 0, 'result' => 'Company  #'.$company->getId().' is created');
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('company'));
				}
			}
		}
		
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();

		return array( 'companies' => $companies, 'form' => $form->createView());
	}
	
    /**
     * @Route("/company/{id}/del", name="company_del")
     */
    public function delAction($id, Request $request)
    {
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($id);
						
		if (!$company) 
		{
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'result' => 'No company found for id '.$id);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No company found for id '.$id);
			}
		}
		
		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($company);
		$em->flush();
		
		if ($request->isXmlHttpRequest()) 
		{
			$result = array('has_error' => 0, 'result' => 'Company  #'.$id.' is removed');
			$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		else
		{
			return $this->redirect($this->generateUrl('company'));
		}
    }
	
    /**
     * @Route("/company/{id}/edit", name="company_edit")
	 * @Template()
     */
    public function editAction($id, Request $request)
    {
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($id);
						
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'result' => 'No company found for id '.$id);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No company found for id '.$id);
			}
		}
		
		$form = $this->createForm(new CompanyType(), $company);
					
		if ($request->getMethod() == 'POST')
		{
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$product = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($company);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$result = array('has_error' => 0, 'result' => 'Company #'.$id.' is updated');
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('company'));
				}
			}
		}


		return array('form' => $form->createView(), 'company' => $company);
    }
	
    /**
     * @Route("/company/{id}", name="company_show")
     * @Template()
     */
    public function showAction($id, Request $request)
    {
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($id);
						
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'result' => 'No company found for id '.$id);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No company found for id '.$id);
			}
		}
	
		return array('company' => $company);
	}
}
