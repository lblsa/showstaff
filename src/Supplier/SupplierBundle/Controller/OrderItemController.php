<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Supplier\SupplierBundle\Entity\Company;
use Supplier\SupplierBundle\Entity\Restaurant;
use Supplier\SupplierBundle\Entity\OrderItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;

class OrderItemController extends Controller
{
    /**
     * @Route(	"company/{cid}/restaurant/{rid}/order/{booking_date}", 
     * 			name="OrderItem_list", 
     * 			requirements={	"_method" = "GET",
	 *							"booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0})
     * @Template()
     * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN")
     */
    public function listAction($cid, $rid, $booking_date, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden Company');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
				}
			}

		
			// check restaurant {rid} for admin restaurant
			if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') && !$this->get('security.context')->isGranted('ROLE_ORDER_MANAGER'))
			{
				$restaurants = $permission->getRestaurants();
				if (!$restaurants)
				{
					if ($request->isXmlHttpRequest()) 
					{
						$code = 403;
						$result = array('code' => $code, 'message' => 'Forbidden Restaurant');
						return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					} else {
						throw new AccessDeniedHttpException('Forbidden Restaurant');
					}
				}
				else
				{
					$available_restaurants = array();
					foreach ($restaurants AS $r)
						$available_restaurants[] = $r->getId();
						
					if (!in_array($rid, $available_restaurants))
					{
						if ($request->isXmlHttpRequest()) 
						{
							$code = 403;
							$result = array('code' => $code, 'message' => 'Forbidden Restaurant');
							return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
							
						} else {
							throw new AccessDeniedHttpException('Forbidden Restaurant');
						}
					}
				}
			}
		}
		
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');
			
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findOneCompanyOneRestaurant($cid, $rid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No restaurant found for id '.$rid.' in company #'.$cid);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			else
			{
				throw $this->createNotFoundException('No restaurant found for id '.$rid.' in company #'.$cid);
			}
		}
		
		$restaurants = $company->getRestaurants();
		foreach ($restaurants AS $r) $restaurant = $r;

		$suppler_products = $this->getDoctrine()
								->getRepository('SupplierBundle:SupplierProducts')
								->findByCompany($cid);

		$products_array = array();
		foreach ($suppler_products as $sp)
		{
			if ($sp->getProduct()->getActive() && $sp->getActive() && $sp->getSupplier()->getActive())
			{
				$products_array[$sp->getProduct()->getId()] = array(	'id' => $sp->getProduct()->getId(),
																		'name'=> $sp->getProduct()->getName(), 
																		'unit' => $sp->getProduct()->getUnit(),
																		'use' => 0 );
			}
		}

		
		$bookings = $this->getDoctrine()
						->getRepository('SupplierBundle:OrderItem')
						->findBy( array(	'company'=>$cid, 'restaurant'=>$rid, 'date' => $booking_date) );

		$bookings_array = array();
		
		if ($bookings)
		{
			foreach ($bookings AS $p)
			{
				if ($p->getProduct()->getActive() && $p->getSupplier()->getActive())
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
		}
		
		$products_array = array_values($products_array); 

		if ($booking_date<date('Y-m-d'))
		{
			$edit_mode = false;
		}
		else
		{
			$order = $this->getDoctrine()
						->getRepository('SupplierBundle:Order')
						->findOneBy( array(	'company'=>$cid, 'date' => date('Y-m-d')) );
			
			if(!$order || $this->get('security.context')->isGranted('ROLE_ORDER_MANAGER'))
				$edit_mode = true;
			else
			{
				$edit_mode = !(boolean)$order->getCompleted();
			}
			
		}
		
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $bookings_array);
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
			header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");// HTTP/1.0
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}

		return array(	'restaurant' => $restaurant, 
						'company' => $company,
						'booking_date' => $booking_date,
						'edit_mode' => $edit_mode );
		
	}

	/**
	* @Route(	"company/{cid}/restaurant/{rid}/order/{booking_date}",
	* 			name="OrderItem_ajax_create",
	* 			requirements={	"_method" = "POST", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	*			defaults={"booking_date" = 0})
	* @Secure(roles="ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	*/
	public function ajaxcreateAction($cid, $rid, $booking_date, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden Company');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
				}
			}
			
			
			// check restaurant {rid} for admin restaurant
			if (	$this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') && 
					!$this->get('security.context')->isGranted('ROLE_ORDER_MANAGER') &&
					!$this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN')
				)
			{
				$restaurants = $permission->getRestaurants();
				if (!$restaurants)
				{
					if ($request->isXmlHttpRequest()) 
					{
						$code = 403;
						$result = array('code' => $code, 'message' => 'Forbidden Restaurant');
						return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
						
					} else {
						throw new AccessDeniedHttpException('Forbidden Restaurant');
					}
				}
				else
				{
					$available_restaurants = array();
					foreach ($restaurants AS $r)
						$available_restaurants[] = $r->getId();
						
					if (!in_array($rid, $available_restaurants))
					{
						if ($request->isXmlHttpRequest()) 
						{
							$code = 403;
							$result = array('code' => $code, 'message' => 'Forbidden Restaurant');
							return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
							
						} else {
							throw new AccessDeniedHttpException('Forbidden Restaurant');
						}
					}
				}
			}
		}
		
		if ($booking_date == '0' || $booking_date < date('Y-m-d'))
			$booking_date = date('Y-m-d');

		$restaurant = $this->getDoctrine()
						->getRepository('SupplierBundle:Restaurant')
						->findOneByIdJoinedToCompany($rid, $cid);

		if (!$restaurant) {
			$code = 404;
			$result = array('code' => $code, 'result' => 'No restaurant found for id '.$rid.' in company #'.$cid);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			
		}
		
		$company = $restaurant->getCompany();
		
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN'))
		{
			$order = $this->getDoctrine()
						->getRepository('SupplierBundle:Order')
						->findOneBy( array(	'company'=>$cid, 'date' => $booking_date) );
			
			if($order)
			{
				if($order->getCompleted())
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Order is completed. You can not create order.');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				}
			}
		}
		
		$model = (array)json_decode($request->getContent());
		
		if ( count($model) > 0 && isset($model['product']) && isset($model['amount']) )
		{
			if ( $model['amount'] == "0" )
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'Amount should not be 0');
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			
			
			$product = $this->getDoctrine()
							->getRepository('SupplierBundle:Product')
							->find((int)$model['product']);
									
			if (!$product)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No product found for id '.$pid);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			
			$model['amount'] = str_replace(',', '.', $model['amount']);
			$amount = 0 + $model['amount'];
		
			$validator = $this->get('validator');
			$booking = new OrderItem();
			$booking->setProduct($product);
			$booking->setDate($booking_date);
			$booking->setAmount($amount);
			$booking->setCompany($company);
			$booking->setRestaurant($restaurant);
			
			$supplier_products = $this->getDoctrine()
									->getRepository('SupplierBundle:SupplierProducts')
									->findBy(
										array('company'=>$company->getId(), 'product'=>$product->getId()), 
										array('prime'=>'DESC','price' => 'ASC'),
										1 ); // Сортируем по первичным, потом по цене с лимитом 1. Первый и будет тем, что надо.
			
			if ($supplier_products)
			{
				$booking->setSupplier($supplier_products[0]->getSupplier());
			}
			else
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No supplier found for product #'.$product->getId());
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			
			$errors = $validator->validate($booking);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
					
				$code = 400;
				$result = array('code' => $code, 'message'=>$errorMessage);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				
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
														'product' => $product->getId(),		));
				
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			
			}
			
		
		}
	
		$code = 400;
		$result = array('code' => $code, 'message'=> 'Invalid request');
		return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
	}
	
	/**
	 * @Route(	"/company/{cid}/restaurant/{rid}/order/{booking_date}/{bid}", 
	 * 				name="OrderItem_ajax_delete", 
 	 * 				requirements={	"_method" = "DELETE", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={"booking_date" = 0})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	 public function ajaxdeleteAction($cid, $rid, $booking_date, $bid)
	 {
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden Company');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
				}
			}
			
			// check restaurant {rid} for admin restaurant
			if (	$this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') && 
					!$this->get('security.context')->isGranted('ROLE_ORDER_MANAGER') &&
					!$this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN')
				)
			{
				$restaurants = $permission->getRestaurants();
				if (!$restaurants)
				{
					if ($request->isXmlHttpRequest()) 
					{
						$code = 403;
						$result = array('code' => $code, 'message' => 'Forbidden Restaurant');
						return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					} else {
						throw new AccessDeniedHttpException('Forbidden Restaurant');
					}
				}
				else
				{
					$available_restaurants = array();
					foreach ($restaurants AS $r)
						$available_restaurants[] = $r->getId();
						
					if (!in_array($rid, $available_restaurants))
					{
						if ($request->isXmlHttpRequest()) 
						{
							$code = 403;
							$result = array('code' => $code, 'message' => 'Forbidden Restaurant');
							return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
						} else {
							throw new AccessDeniedHttpException('Forbidden Restaurant');
						}
					}
				}
			}	
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($cid);
		
		if (!$company) {
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
		
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN'))
		{
			$order = $this->getDoctrine()
						->getRepository('SupplierBundle:Order')
						->findOneBy( array(	'company'=>$cid, 'date' => date('Y-m-d')) );
			
			if($order)
				if($order->getCompleted())
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Order is completed. You can not edit order.');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				}
		}
		
		$restaurant = $this->getDoctrine()
					->getRepository('SupplierBundle:Restaurant')
					->find($rid);
					
		if (!$restaurant)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No restaurant found for id '.$rid);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
	
		$booking = $this->getDoctrine()
					->getRepository('SupplierBundle:OrderItem')
					->find($bid);
		
		if (!$booking)
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $bid, 'message' => 'No oreder item found for id '.$rid);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}

		if ($booking->getDate() < date('Y-m-d') )
		{
			$code = 403;
			$result = array('code' => $code, 'message' => 'You can not remove the old booking');
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
		else
		{
			$em = $this->getDoctrine()->getEntityManager();				
			$em->remove($booking);
			$em->flush();
		
			$code = 200;
			$result = array('code' => $code, 'data' => $bid);
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}
	}


	/**
	 * @Route(	"company/{cid}/restaurant/{rid}/order/{booking_date}/{bid}", 
	 * 			name="OrderItem_ajax_update", 
	 * 			requirements={	"_method" = "PUT", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={"booking_date" = 0})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	public function ajaxupdateAction($cid, $rid, $booking_date, $bid, Request $request)
	{ 
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
			{
				if ($request->isXmlHttpRequest()) 
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden Company');
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
				}
			}
			
			// check restaurant {rid} for admin restaurant
			if (	$this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') && 
					!$this->get('security.context')->isGranted('ROLE_ORDER_MANAGER') &&
					!$this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN')
				)
			{
				$restaurants = $permission->getRestaurants();
				if (!$restaurants)
				{
					if ($request->isXmlHttpRequest()) 
					{
						$code = 403;
						$result = array('code' => $code, 'message' => 'Forbidden Restaurant');
						return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					} else {
						throw new AccessDeniedHttpException('Forbidden Restaurant');
					}
				}
				else
				{
					$available_restaurants = array();
					foreach ($restaurants AS $r)
						$available_restaurants[] = $r->getId();
						
					if (!in_array($rid, $available_restaurants))
					{
						if ($request->isXmlHttpRequest()) 
						{
							$code = 403;
							$result = array('code' => $code, 'message' => 'Forbidden Restaurant');
							return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
						} else {
							throw new AccessDeniedHttpException('Forbidden Restaurant');
						}
					}
				}
			}
		}
		
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
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			
			$company = $this->getDoctrine()
							->getRepository('SupplierBundle:Company')
							->find($cid);
			if (!$company) {
				$code = 404;
				$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			
			if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN'))
			{
				$order = $this->getDoctrine()
							->getRepository('SupplierBundle:Order')
							->findOneBy( array(	'company'=>$cid, 'date' => date('Y-m-d')) );
							
				if($order)
					if($order->getCompleted())
					{
						$code = 403;
						$result = array('code' => $code, 'message' => 'Order is completed. You can not edit order.');
						return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					}
			}
			
			$restaurant = $this->getDoctrine()
						->getRepository('SupplierBundle:Restaurant')
						->find($rid);
			if (!$restaurant)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No restaurant found for id '.$rid);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
		

			
			$booking = $this->getDoctrine()
									->getRepository('SupplierBundle:OrderItem')
									->find($bid);
			if (!$booking)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No booking found for id '.$rid);
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			
			if ($booking->getDate() < date('Y-m-d') )
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'You can not edit the old booking');
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			}
			else
			{
				$model['amount'] = str_replace(',', '.', $model['amount']);
				$amount = 0 + $model['amount'];
				
				$validator = $this->get('validator');
				
				$product = $this->getDoctrine()
						->getRepository('SupplierBundle:Product')
						->find((int)$model['product']);
						
				if (!$product)
				{
					$code = 404;
					$result = array('code' => $code, 'message' => 'No product found for id '.$pid);
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				}
								
				$booking->setAmount($amount);
				$booking->setProduct($product);
				
				$supplier_products = $this->getDoctrine()
									->getRepository('SupplierBundle:SupplierProducts')
									->findBy(
										array('company'=>$company->getId(), 'product'=>$product->getId()), 
										array('prime'=>'DESC','price' => 'ASC'),
										1 ); // Сортируем по первичным, потом по цене с лимитом 1. Первый и будет тем, что надо.
			
				if ($supplier_products)
				{
					$booking->setSupplier($supplier_products[0]->getSupplier());
				}
				else
				{
					$code = 404;
					$result = array('code' => $code, 'message' => 'No supplier found for product #'.$product->getId());
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				}

				$errors = $validator->validate($booking);
				
				if (count($errors) > 0) {
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();
					
					$code = 400;
					$result = array('code'=>$code, 'message'=>$errorMessage);
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					
				} else {
					
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($booking);
					$em->flush();
					
					$code = 200;
					
					$result = array('code'=> $code, 
											'data' => array(	'name' => $booking->getProduct()->getName(),
																'amount' => $booking->getAmount(),
																'product' => $booking->getProduct()->getId(),
																'id' => $booking->getId()	));
					return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				}
			}
		}
			
		$code = 400;
		$result = array('code'=> $code, 'message' => 'Invalid request');
		return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
	 
	}
}

