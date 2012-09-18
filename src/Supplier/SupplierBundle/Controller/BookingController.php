<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Supplier\SupplierBundle\Entity\Company;
use Supplier\SupplierBundle\Entity\Restaurant;
use Supplier\SupplierBundle\Entity\Booking;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class BookingController extends Controller
{
    /**
     * @Route(	"company/{cid}/restaurant/{rid}/booking/{booking_date}", 
     * 				name="content_booking_list", 
     * 				requirements={"_method" = "GET", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *					defaults={"booking_date" = 0})
     * @Template()
     */
    public function listAction($cid, $rid, $booking_date, Request $request)
    {
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');
			
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findOneCompanyOneRestaurant($cid, $rid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No restaurant found for id '.$rid.' in company #'.$cid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				throw $this->createNotFoundException('No restaurant found for id '.$rid.' in company #'.$cid);
			}
		}
		
		$restaurants = $company->getRestaurants();
		foreach ($restaurants AS $r) $restaurant = $r;
		
		$products = $company->getProducts();

		$products_array = array();
		if ($products)
		{
			foreach ($products AS $p)
				$products_array[$p->getId()] = array( 'id' => $p->getId(),
																			'name'=> $p->getName(), 
																			'unit' => $p->getUnit(),
																			'use' => 0
																		);
		}
		
		$restaurants_array = array();
		
		$bookings = $this->getDoctrine()
							->getRepository('SupplierBundle:Booking')
							->findBy( array(	'company'=>$cid, 'date' => $booking_date) );

		$bookings_array = array();
		if ($bookings)
		{
			foreach ($bookings AS $p)
			{
				$bookings_array[] = array(	'id' => $p->getId(),
															'amount' => $p->getAmount(),
															'product' => $p->getProduct()->getId(),
															'name' => $p->getProduct()->getName(),
										);
				if (isset($products_array[$p->getProduct()->getId()]))
					$products_array[$p->getProduct()->getId()]['use'] = 1;
			}
		}
		$products_array = array_values($products_array); 

		return array(	'restaurant' => $restaurant, 
								'company' => $company,
								'products' => $products,
								'bookings' => $bookings,
								'bookings_json' => json_encode($bookings_array),
								'products_json' => json_encode($products_array),
								'booking_date' => $booking_date,
								'edit_mode' => $booking_date<date('Y-m-d')?false:true );
		
	}

	/**
	 * @Route(	"company/{cid}/restaurant/{rid}/booking", 
	 * 				name="booking_ajax_create",  
	 * 				requirements={"_method" = "POST"})
	 */
	public function ajaxcreateAction($cid, $rid, Request $request)
	{
		$restaurant = $this->getDoctrine()
						->getRepository('SupplierBundle:Restaurant')
						->findOneByIdJoinedToCompany($rid, $cid);


		if (!$restaurant) {
			$code = 404;
			$result = array('code' => $code, 'result' => 'No restaurant found for id '.$rid.' in company #'.$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
		$company = $restaurant->getCompany();
		
		$model = (array)json_decode($request->getContent());
		
		if ( count($model) > 0 && isset($model['product']) && isset($model['amount']) )
		{
			if ( $model['amount'] == "0" )
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'Amount should not be 0');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			
			$product = $this->getDoctrine()
							->getRepository('SupplierBundle:Product')
							->find((int)$model['product']);
									
			if (!$product)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No product found for id '.$pid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$model['amount'] = str_replace(',', '.', $model['amount']);
			$amount = 0 + $model['amount'];
		
			$validator = $this->get('validator');
			$booking = new Booking();
			$booking->setProduct($product);
			$booking->setDate(date('Y-m-d'));
			$booking->setAmount($amount);
			$booking->setCompany($company);
			$booking->setRestaurant($restaurant);
			
			$errors = $validator->validate($booking);
			
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
				$em->persist($booking);
				$em->flush();
				
				$code = 200;
				$result = array(	'code' => $code, 
											'data' => array(	'id' => $booking->getId(),
																		'company' => $company->getId(), 
																		'date' => $booking->getDate(), 
																		'amount' => $booking->getAmount(),
																		'restaurant' => $restaurant->getId(),
																		'product' => $product->getId(),
																	)
										);
				
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
	 * @Route(	"/company/{cid}/restaurant/{rid}/booking/{bid}", 
	 * 				name="booking_ajax_delete", 
 	 * 				requirements={"_method" = "DELETE"})
	 */
	 public function ajaxdeleteAction($cid, $rid, $bid)
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
	
		$booking = $this->getDoctrine()
					->getRepository('SupplierBundle:Booking')
					->find($bid);
		
		if (!$booking)
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $bid, 'message' => 'No booking found for id '.$rid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}

		if ($booking->getDate() < date('Y-m-d') )
		{
			$code = 403;
			$result = array('code' => $code, 'message' => 'You can not remove the old booking');
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		else
		{
			$em = $this->getDoctrine()->getEntityManager();				
			$em->remove($booking);
			$em->flush();
		
			$code = 200;
			$result = array('code' => $code, 'data' => $bid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
	}


	/**
	 * @Route(	"company/{cid}/restaurant/{rid}/booking/{bid}", 
	 * 			name="booking_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 */
	public function ajaxupdateAction($cid, $rid, $bid, Request $request)
	{
		$model = (array)json_decode($request->getContent());
		
		if	(	count($model) > 0 && 
				isset($model['id']) && 
				is_numeric($model['id']) && 
				$bid == $model['id'] && 
				isset($model['product']) && 
				isset($model['amount'])	)
		{

			if ( $model['amount'] == "0" ||  $model['amount'] == "")
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'Amount should not be 0');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
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
		
			$product = $this->getDoctrine()
									->getRepository('SupplierBundle:Product')
									->find((int)$model['product']);
			if (!$product)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No product found for id '.$pid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$booking = $this->getDoctrine()
									->getRepository('SupplierBundle:Booking')
									->find($bid);
			if (!$booking)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No booking found for id '.$rid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			if ($booking->getDate() < date('Y-m-d') )
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'You can not edit the old booking');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			else
			{
				$model['amount'] = str_replace(',', '.', $model['amount']);
				$amount = 0 + $model['amount'];
				
				$validator = $this->get('validator');
				$booking->setProduct($product);
				$booking->setAmount($amount);
				
				$errors = $validator->validate($booking);
				
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
					$em->persist($booking);
					$em->flush();
					
					$code = 200;
					
					$result = array('code'=> $code, 
											'data' => array(	'name' => $booking->getProduct()->getName(),
																		'amount' => $booking->getAmount(),
																		'product' => $booking->getProduct()->getId(),
																		'id' => $booking->getId(),
																	));
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
			}
		}
			
		$code = 400;
		$result = array('code'=> $code, 'message' => 'Invalid request');
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
		 
	}

}

