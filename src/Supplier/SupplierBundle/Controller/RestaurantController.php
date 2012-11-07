<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Company;
use Supplier\SupplierBundle\Entity\Restaurant;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
	 * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_ADMIN, ROLE_ORDER_MANAGER")
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
					return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
				else
					throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
				return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
			else
				throw $this->createNotFoundException('No company found for id '.$cid);
		}
		
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN'))
			$restaurants = $permission->getRestaurants();
			
		if ($this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN'))
			$restaurants = $company->getRestaurants();
			
		if ($this->get('security.context')->isGranted('ROLE_ORDER_MANAGER'))
			$restaurants = $company->getRestaurants();

			
		$restaurants_array = array();
		
		if ($restaurants)
			foreach ($restaurants AS $p)
				$restaurants_array[] = array(	'id' => $p->getId(),
												'name'=> $p->getName(),
												'address'=> $p->getAddress(),
												'director'=> $p->getDirector(), );
												
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN'))	
		{
			return $this->render('SupplierBundle:Restaurant:listToOrder.html.twig', array(	'restaurants' => $restaurants_array,
																							'company' => $company 	));
		}

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
			
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $restaurants_array);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
		
		return array(	'company' => $company		);
	}
	
	/**
	 * @Route(	"company/{cid}/restaurant/{rid}", name="restaurant_ajax_update", requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	 public function ajaxupdateAction($cid, $rid, Request $request)
	 {
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
					return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
				else
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
				return new Response('No restaurant found for id '.$rid, 404, array('Content-Type' => 'application/json'));
			
			$validator = $this->get('validator');

			$restaurant->setName($model['name']);
			$restaurant->setAddress($model['address']);
			$restaurant->setDirector($model['director']);
			
			$errors = $validator->validate($restaurant);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
				
				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($restaurant);
				$em->flush();
				
				$code = 200;
				
				$result = array('code'=> $code, 'data' => array(	'name' => $restaurant->getName(),
																	'address' => $restaurant->getAddress(),
																	'director' => $restaurant->getDirector(),
																));
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));			
			}
		}

		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	 }
	 
	/**
	 * @Route(	"company/{cid}/restaurant/{rid}", name="restaurant_ajax_delete", requirements={"_method" = "DELETE"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function ajaxdeleteAction($cid, $rid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
					return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
				else
					throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$restaurant = $this->getDoctrine()
					->getRepository('SupplierBundle:Restaurant')
					->find($rid);
					
		if (!$restaurant)
			return new Response('No restaurant found for id '.$rid, 404, array('Content-Type' => 'application/json'));
		

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($restaurant);
		$em->flush();
		
		$code = 200;
		$result = array('code' => $code, 'data' => $rid);
		return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
	}
	
	/**
	 * @Route(	"company/{cid}/restaurant", name="restaurant_ajax_create", requirements={"_method" = "POST"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
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
					return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
				else
					throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($cid);
						
		if (!$company)
			return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
		
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
					
				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
				
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
				
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));		
			}
		}
		
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));	 
	}
}
