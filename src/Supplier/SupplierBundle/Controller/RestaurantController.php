<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Company;
use Supplier\SupplierBundle\Entity\Restaurant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\RestaurantType;
use JMS\SecurityExtraBundle\Annotation\Secure;

class RestaurantController extends Controller
{
	
	/**
	 * @Route(	"/company/{cid}/restaurant", name="restaurant",	requirements={"_method" = "GET"})
	 * @Template()
	 * @Secure(roles="ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	public function listAction($cid, Request $request)
	{		
		$user = $this->get('security.context')->getToken()->getUser();
		
		$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

		if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
		{
			if ($request->isXmlHttpRequest()) 
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden Company');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			} else {
				throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		
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
		
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN'))
			$restaurants = $permission->getRestaurants();
			
		if ($this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN'))
			$restaurants = $company->getRestaurants();

		$restaurants_array = array();
		
		if ($restaurants)
		{
			foreach ($restaurants AS $p)
			{
				$restaurants_array[] = array(	'id' => $p->getId(),
												'name'=> $p->getName(),
												'address'=> $p->getAddress(),
												'director'=> $p->getDirector(),
												);
			}
		}
		
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $restaurants_array);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
		return array(	'company' => $company,
						'restaurants_json' => json_encode($restaurants_array)
						);
	}
	
	/**
	 * @Route(	"company/{cid}/restaurant/{rid}", name="restaurant_ajax_update", requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	 public function ajaxupdateAction($cid, $rid, Request $request)
	 {
		$user = $this->get('security.context')->getToken()->getUser();
		
		$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

		if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
		{
			if ($request->isXmlHttpRequest()) 
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden Company');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			} else {
				throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $rid == $model['id'])
		{
			$restaurant = $this->getDoctrine()
							->getRepository('SupplierBundle:Restaurant')
							->find($model['id']);
			
			if (!$restaurant)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No restaurant found for id '.$rid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$validator = $this->get('validator');

			$restaurant->setName($model['name']);
			$restaurant->setAddress($model['address']);
			$restaurant->setDirector($model['director']);
			
			$errors = $validator->validate($restaurant);
			
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
				$em->persist($restaurant);
				$em->flush();
				
				$code = 200;
				
				$result = array('code'=> $code, 'data' => array(	'name' => $restaurant->getName(),
																	'address' => $restaurant->getAddress(),
																	'director' => $restaurant->getDirector(),
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
	 * @Route(	"company/{cid}/restaurant/{rid}", name="restaurant_ajax_delete", requirements={"_method" = "DELETE"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function ajaxdeleteAction($cid, $rid, Request $request)
	{
			
		$user = $this->get('security.context')->getToken()->getUser();
		
		$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

		if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
		{
			if ($request->isXmlHttpRequest()) 
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden Company');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			} else {
				throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$restaurant = $this->getDoctrine()
					->getRepository('SupplierBundle:Restaurant')
					->find($rid);
					
		if (!$restaurant)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No restaurant found for id '.$rid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($restaurant);
		$em->flush();
		
		$code = 200;
		$result = array('code' => $code, 'data' => $rid);
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	}
	
	/**
	 * @Route(	"company/{cid}/restaurant", 
	 * 			name="restaurant_ajax_create", 
	 * 			requirements={"_method" = "POST"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function ajaxcreateAction($cid, Request $request)
	{	
		$user = $this->get('security.context')->getToken()->getUser();
		
		$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

		if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
		{
			if ($request->isXmlHttpRequest()) 
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden Company');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			} else {
				throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
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
			$restaurant = new Restaurant();
			$restaurant->setName($model['name']);
			$restaurant->setAddress($model['address']);
			$restaurant->setDirector($model['director']);
			
			$errors = $validator->validate($restaurant);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
					
				$code = 400;
				$result = array('code' => $code, 'message'=>$errorMessage);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
				
			} else {
				
				$restaurant->setCompany($company);
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($restaurant);
				$em->flush();
				
				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $restaurant->getId(),
																		'name' => $restaurant->getName(),
																		'address' => $restaurant->getAddress(),
																		'director' => $restaurant->getDirector(),
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
	
	private function checkUserRightsToRestaurant($rid, $request, $user)
	{
		$available_restaurants = array();
		$user_restaurants = $user->getRestaurants();
		
		if ($user_restaurants)
			foreach($user_restaurants AS $r)
				$available_restaurants[] = $r->getId();
				

		if (!in_array($rid, $available_restaurants)) // , и проверим к какому ресторану вы назначены
		{
			if ($request->isXmlHttpRequest()) 
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden Restaurant');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			} else {
				throw new AccessDeniedHttpException('Forbidden Restaurant');
			}
		}
	}
}
