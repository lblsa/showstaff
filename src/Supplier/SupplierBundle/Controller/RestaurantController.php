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
	 * @Route(	"api/company/{cid}/restaurant.{_format}",
	 *			name="API_restaurant",
	 *			requirements={"_method" = "GET", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Template()
	 * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_ADMIN, ROLE_ORDER_MANAGER")
	 */
	public function API_listAction($cid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
				return new Response('Forbidden Company', 403, array('Content-Type' => 'application/json'));
		}
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		
		if (!$company)
			return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
		
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') && !$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
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
												'director'=> $p->getDirector());

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0

		$result = array('code' => 200, 'data' => $restaurants_array);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	/**
	 * @Route(	"/company/{cid}/restaurant", name="restaurant",	requirements={"_method" = "GET"})
	 * @Template()
	 * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_ADMIN, ROLE_ORDER_MANAGER")
	 */
	public function listAction($cid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();

		$restaurants_list = array();

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission)
				throw new AccessDeniedHttpException('Forbidden Company');
			else
			{
				 if ($permission->getCompany()->getId() != $cid) // проверим из какой компании
				 	throw new AccessDeniedHttpException('Forbidden Company');

				$restaurants = $permission->getRestaurants();

				if ($restaurants)
					foreach ($restaurants as $r)
						$restaurants_list[$r->getId()] = $r->getName();
			}
		}
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		
		if (!$company)
			throw $this->createNotFoundException('No company found for id '.$cid);
				
		if ($this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN'))
		{
			$restaurants = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->findByCompany((int)$cid);

			if ($restaurants)
				foreach ($restaurants as $r)
					$restaurants_list[$r->getId()] = $r->getName();
		}

		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') && !$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
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
												
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') && !$this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN'))	
			return $this->render('SupplierBundle:Restaurant:listToOrder.html.twig', array(	'restaurants'		=> $restaurants_array,
																							'restaurants_list'	=> $restaurants_list,
																							'company'			=> $company 	));

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
		
		return array(	'company' => $company, 'restaurants_list' => $restaurants_list	);
	}
	
	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}.{_format}",
	 *			name="API_restaurant_update",
	 *			requirements={"_method" = "PUT", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	 public function API_updateAction($cid, $rid, Request $request)
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
				
				$result = array('code'=> 200, 'data' => array(	'name' => $restaurant->getName(),
																	'address' => $restaurant->getAddress(),
																	'director' => $restaurant->getDirector(),
																));
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));		
			}
		}

		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	 }
	 
	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}.{_format}",
	 *			name="API_restaurant_delete",
	 *			requirements={"_method" = "DELETE", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function API_deleteAction($cid, $rid, Request $request)
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
		
		$result = array('code' => 200, 'data' => $rid);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	
	/**
	 * @Route(	"api/company/{cid}/restaurant.{_format}",
	 *			name="API_restaurant_create",
	 *			requirements={"_method" = "POST", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function API_createAction($cid, Request $request)
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
				
				$result = array(	'code	' => 200, 'data' => array(	'id' => $restaurant->getId(),
																		'name' => $restaurant->getName(),
																		'address' => $restaurant->getAddress(),
																		'director' => $restaurant->getDirector(),
																	));
				
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));	 
	}
}
