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

		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		if (!$company)
			return new Response('Компания не найдена', 404, array('Content-Type' => 'application/json'));
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
					
		$restaurants = $company->getRestaurants();
		$available_restaurants = array();
		if ($restaurants)
		{
			foreach ($restaurants as $r)
			{
				// list available restaraunts for restaurant manager OR all for admin
				if (	$this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN') || 
						$this->get('security.context')->isGranted('ROLE_ORDER_MANAGER') ||
						$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN') )
				{
					$available_restaurants[] = $r;
				}
				else
				{
					if (false !== $securityContext->isGranted('VIEW', $r))
						$available_restaurants[] = $r;
				}
			}
		}

		$restaurants_array = array();		
		if ($available_restaurants)
			foreach ($available_restaurants AS $p)
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

		// check exist this company
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		if (!$company)
			throw $this->createNotFoundException('Компания не найдена');

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				throw new AccessDeniedHttpException('Нет доступа к компании');

		$available_restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
		
		$restaurants_array = array();
		$restaurants_list = array();

		foreach ($available_restaurants AS $p)
		{
			$restaurants_array[] = array(	'id' => $p->getId(),
											'name'=> $p->getName(),
											'address'=> $p->getAddress(),
											'director'=> $p->getDirector(), );
			$restaurants_list[$p->getId()] = $p->getName();
		}
												
		if (!$this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN'))	
			return $this->render('SupplierBundle:Restaurant:listToOrder.html.twig', array(	'restaurants'		=> $restaurants_array,
																							'restaurants_list'	=> $restaurants_list,
																							'company'			=> $company 	));

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
		
		return array('company' => $company, 'restaurants_list' => $restaurants_list	);
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
		
		// check exist this company
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		if (!$company)
			return new Response('Компания не найдена', 400, array('Content-Type' => 'application/json'));

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $rid == $model['id'])
		{
			$restaurant = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->find($model['id']);
			
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
		
		// check exist this company
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		if (!$company)
			return new Response('Компания не найдена', 400, array('Content-Type' => 'application/json'));
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
		$restaurant = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->find($rid);
		if (!$restaurant)
			return new Response('Ресторан не найден', 400, array('Content-Type' => 'application/json'));
		

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

		// check exist this company
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		if (!$company)
			return new Response('Компания не найдена', 400, array('Content-Type' => 'application/json'));

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
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
