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


/**
 * Permission controller.
 *
 * @Route("/company")
 */
class CompanyController extends Controller
{
	
	/**
	 * @Route(	"", 
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
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
		
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $companies_array);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}

		return array( 'companies' => $companies, 'companies_json' => json_encode($companies_array) );
	}	
	
	/**
	 * @Route(	"/{cid}", 
	 * 			name="company_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
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
			$company->setInn($model['inn']);
			
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
	 * @Route(	"", 
	 * 			name="company_ajax_create", 
	 * 			requirements={"_method" = "POST"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
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
	 * @Route(	"/{cid}", 
	 * 			name="company_ajax_delete", 
	 * 			requirements={"_method" = "DELETE"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
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
