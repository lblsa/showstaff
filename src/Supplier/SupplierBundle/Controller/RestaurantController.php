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


class RestaurantController extends Controller
{
	
	/**
	 * @Route(	"/company/{cid}/restaurant", 
	 * 			name="restaurant",
	 * 			requirements={"_method" = "GET"})
	 * @Template()
	 */
	public function listAction($cid, Request $request)
	{		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany($cid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'result' => 'No company found for id '.$cid);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No company found for id '.$cid);
			}
		}
		
		$restaurant = new Restaurant();
		
		$form = $this->createForm(new RestaurantType(), $restaurant);
		
		if ($request->getMethod() == 'POST')
		{			
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$restaurant = $form->getData();
				$restaurant->setCompany($company);		
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($restaurant);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$result = array('has_error' => 0, 'result' => 'Restaurant #'.$restaurant->getId().' is created');
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('restaurant', array('cid' => $cid)));
				}
			}
		}
		
		$restaurants = $company->getRestaurants();

		$restaurants_array = array();
		
		if ($restaurants)
		{
			foreach ($restaurants AS $p)
				$restaurants_array[] = array(	'id' => $p->getId(),
												'name'=> $p->getName(),
												'address'=> $p->getAddress(),
												'director'=> $p->getDirector(),
												);
		}

		return array(	'restaurants' => $restaurants, 
						'company' => $company,
						'form' => $form->createView(),
						'restaurants_json' => json_encode($restaurants_array)
						);
	}
	
    /**
     * @Route("/company/{cid}/restaurant/{rid}/delete", name="restaurant_del")
     */
    public function delAction($cid, $rid, Request $request)
    {
		$restaurant = $this->getDoctrine()
						->getRepository('SupplierBundle:Restaurant')
						->find($rid);
						
		if (!$restaurant) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'result' => 'No restaurant found for id '.$rid);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No restaurant found for id '.$rid);
			}
		}
		
		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($restaurant);
		$em->flush();
			
		if ($request->isXmlHttpRequest()) 
		{
			$result = array('has_error' => 0, 'result' => 'Restaurant #'.$rid.' is deleted');
			$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		else
		{
			return $this->redirect($this->generateUrl('restaurant', array('cid'=>$cid)));
		}
    }
	
    /**
     * @Route("/company/{cid}/restaurant/{rid}/edit", name="restaurant_edit")
	 * @Template()
     */
    public function editAction($cid, $rid, Request $request)
    {
		$restaurant = $this->getDoctrine()
						->getRepository('SupplierBundle:Restaurant')
						->findOneByIdJoinedToCompany($rid, $cid);
						
		if (!$restaurant) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'result' => 'No restaurant found for id '.$rid);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No restaurant found for id '.$rid);
			}
		}
		
		$company = $restaurant->getCompany();
		
		$form = $this->createForm(new RestaurantType(), $restaurant);
		
		if ($request->getMethod() == 'POST')
		{		
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$restaurant = $form->getData();
				$restaurant->setCompany($company);		
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($restaurant);
				$em->flush();
				
				if ($request->isXmlHttpRequest()) 
				{
					$result = array('has_error' => 0, 'result' => 'Restaurant #'.$rid.' is updated');
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				else
				{
					return $this->redirect($this->generateUrl('restaurant', array('cid' => $cid)));
				}
			}
		}


		return array('restaurant' => $restaurant, 'company' => $company, 'form' => $form->createView());
    }
	
    /**
     * @Route(	"/company/{cid}/restaurant/{rid}", 
     * 			name="restaurant_show",
     *			requirements={"_method" = "GET"}))
     * @Template()
     */
    public function showAction($cid, $rid)
    {
		$restaurant = $this->getDoctrine()
						->getRepository('SupplierBundle:Restaurant')
						->findOneByIdJoinedToCompany($rid, $cid);
						
		if (!$restaurant) {
			if ($request->isXmlHttpRequest()) 
			{
				$result = array('has_error' => 1, 'result' => 'No restaurant found for id '.$rid);
				$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No restaurant found for id '.$rid);
			}
		}
		
		$company = $restaurant->getCompany();
	
		return array('company' => $company, 'restaurant' => $restaurant);
	}
	
	/**
	 * @Route(	"company/{cid}/restaurant/{rid}", 
	 * 			name="restaurant_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 */
	 public function ajaxupdateAction($cid, $rid, Request $request)
	 {		 
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
	 * @Route(	"company/{cid}/restaurant/{rid}", 
	 * 			name="restaurant_ajax_delete", 
	 * 			requirements={"_method" = "DELETE"})
	 */
	public function ajaxdeleteAction($cid, $rid, Request $request)
	{
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
}
