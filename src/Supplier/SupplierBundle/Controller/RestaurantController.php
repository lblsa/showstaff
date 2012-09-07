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
	 * @Route("/company/{cid}/restaurant", name="restaurant")
	 * @Template()
	 */
	public function listAction($cid, Request $request)
	{		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
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
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		$restaurants = $company->getRestaurants();

		return array( 'restaurants' => $restaurants, 'company' => $company, 'form' => $form->createView());
	}
	
    /**
     * @Route("/company/{cid}/restaurant/{rid}/del", name="restaurant_del")
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
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
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
     * @Route("/company/{cid}/restaurant/{rid}", name="restaurant_show")
     * @Template()
     */
    public function showAction($cid, $rid)
    {
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($cid);
						
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
	
		return array('company' => $company, 'restaurant' => $restaurant);
	}
}
