<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Company;
use Supplier\SupplierBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\CompanyType;
use JMS\SecurityExtraBundle\Annotation\Secure;

class CompanyController extends Controller
{
	
	/**
	 * @Route(	"company", 
	 * 			name="company",
	 * 			requirements={"_method" = "GET"})
	 * @Template()
	 * @Secure(roles="ROLE_SUPER_ADMIN")
	 */
	public function listAction(Request $request)
	{
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();

		$companies_array = array();
		
		if ($companies)
		{
			foreach ($companies AS $p)
				$companies_array[] = array( 	'id' => $p->getId(),
												'name'=> $p->getName(), 
												'extended_name' => $p->getExtendedName(),
												'inn' => $p->getInn(),
											);
		}

		return array( 'companies' => $companies, 'companies_json' => json_encode($companies_array) );
	}
	
    /**
     * @Route("company/{id}/del", name="company_del")
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
     * @Route("company/{id}/edit", name="company_edit")
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
     * @Route(	"company/{id}", 
     * 			name="company_show",
     * 			requirements={"_method" = "GET"})
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
	
	
	/**
	 * @Route(	"company/{cid}", 
	 * 			name="company_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 */
	 public function ajaxupdateAction($cid, Request $request)
	 {		 
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $cid == $model['id'])
		{
			$company = $this->getDoctrine()
							->getRepository('SupplierBundle:Company')
							->find($model['id']);
			
			if (!$company)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$validator = $this->get('validator');

			$company->setName($model['name']);
			$company->setExtendedName($model['extended_name']);
			$company->setInn((int)$model['inn']);
			
			$errors = $validator->validate($company);
			
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
				$em->persist($company);
				$em->flush();
				
				$code = 200;
				
				$result = array('code'=> $code, 'data' => array(	'name' => $company->getName(),
																	'extended_name' => $company->getExtendedName(), 
																	'inn' => $company->getInn()
																));
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
	 * @Route(	"company", 
	 * 			name="company_ajax_create", 
	 * 			requirements={"_method" = "POST"})
	 */
	public function ajaxcreateAction(Request $request)
	{
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['inn']) && isset($model['name']))
		{
			$validator = $this->get('validator');
			$company = new Company();
			$company->setName($model['name']);
			$company->setExtendedName($model['extended_name']);
			$company->setInn((int)$model['inn']);
			
			$errors = $validator->validate($company);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
					
				$code = 400;
				$result = array('code' => $code, 'message'=>$errorMessage);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($company);
				$em->flush();
				
				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $company->getId(),
																		'name' => $company->getName(), 
																		'extended_name' => $company->getExtendedName(), 
																		'inn' => $company->getINN()
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
	
	
	/**
	 * @Route(	"company/{cid}", 
	 * 			name="company_ajax_delete", 
	 * 			requirements={"_method" = "DELETE"})
	 */
	public function ajaxdeleteAction($cid, Request $request)
	{
		$company = $this->getDoctrine()
					->getRepository('SupplierBundle:Company')
					->find($cid);
					
		if (!$company)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($company);
		$em->flush();
		
		$code = 200;
		$result = array('code' => $code, 'data' => $cid);
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	}
}
