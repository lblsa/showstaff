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
     * @Route(	"company/{cid}/restaurant/{rid}/order/{booking_date}/", 
     * 			name="OrderItem_list_", 
     * 			requirements={	"_method" = "GET",
	 *							"booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0})
     * @Route(	"company/{cid}/restaurant/{rid}/order/",
	 *			name="OrderItem_list__",
	 *			requirements={"_method" = "GET"},
	 *			defaults={"booking_date" = 0}	)
     * @Template()
     * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN")
     */
    public function listAction($cid, $rid, $booking_date, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();

		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findOneCompanyOneRestaurant($cid, $rid);	
		if (!$company)
			throw $this->createNotFoundException('No restaurant found for id '.$rid.' in company #'.$cid);

		$restaurants_list = array();
		$available_restaurants = array();

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				throw new AccessDeniedHttpException('Нет доступа к компании');

		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
		
		foreach ($restaurants AS $r)
		{
			$available_restaurants[] = $r->getId();
			$restaurants_list[$r->getId()] = $r->getName();
			if ($r->getId() == $rid)
				$restaurant = $r;
		}

		if (!in_array($rid, $available_restaurants) || !isset($restaurant))
			throw new AccessDeniedHttpException('Нет доступа к ресторану');
		
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');

		$suppler_products = $this->getDoctrine()->getRepository('SupplierBundle:SupplierProducts')->findByCompany($cid);

		$products_array = array();
		foreach ($suppler_products as $sp)
			if ($sp->getProduct()->getActive() && $sp->getActive() && $sp->getSupplier()->getActive())
				$products_array[$sp->getProduct()->getId()] = array(	'id' => $sp->getProduct()->getId(),
																		'name'=> $sp->getProduct()->getName(), 
																		'unit' => $sp->getProduct()->getUnit(),
																		'use' => 0 );

		$bookings = $this->getDoctrine()->getRepository('SupplierBundle:OrderItem')->findBy( array(	'company'=>$cid,
																									'restaurant'=>$rid,
																									'date' => $booking_date) );

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
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0

		return array(	'restaurant'		=> $restaurant,
						'restaurants_list'	=> $restaurants_list,
						'company'			=> $company,
						'booking_date'		=> $booking_date );
		
	}
	
	
    /**
     * @Route(	"api/company/{cid}/restaurant/{rid}/order/{booking_date}.{_format}", 
     * 			name="API_OrderItem_list", 
     * 			requirements={	"_method" = "GET",
	 *							"_format" = "json|xml",
	 *							"booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={	"booking_date" = 0,
	 *						"_format" = "json"})
     * @Route(	"api/company/{cid}/restaurant/{rid}/order/{booking_date}.{_format}/", 
     * 			name="API_OrderItem_list_", 
     * 			requirements={	"_method" = "GET",
     *							"_format" = "json|xml",
	 *							"booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={	"booking_date" = 0,
	 *						"_format" = "json"}	)
     * @Route(	"api/company/{cid}/restaurant/{rid}/order/",
	 *			name="API_OrderItem_list__",
	 *			requirements={"_method" = "GET"},
	 *			defaults={"booking_date" = 0})
     * @Template()
     * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN")
     */
    public function API_listAction($cid, $rid, $booking_date, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
			
		$available_restaurants = array();
		foreach ($restaurants AS $r)
			$available_restaurants[] = $r->getId();
			
		if (!in_array($rid, $available_restaurants))
			return new Response('Forbidden Restaurant', 403, array('Content-Type' => 'application/json'));
		
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');
			
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findOneCompanyOneRestaurant($cid, $rid);
		
		if (!$company)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
		
		$restaurants = $company->getRestaurants();
		foreach ($restaurants AS $r) $restaurant = $r;

		$suppler_products = $this->getDoctrine()->getRepository('SupplierBundle:SupplierProducts')->findByCompany($cid);

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

		if ($this->get('security.context')->isGranted('ROLE_ORDER_MANAGER'))
		{
			$edit_mode = 1;
		}
		else
		{
			if ( $booking_date <= date('Y-m-d') ) // можно редактировать только завтрашний заказ
			{
				$edit_mode = 0;
			}
			else
			{
				$order = $this->getDoctrine()->getRepository('SupplierBundle:Order')->findOneBy( array(	'company'=>$cid, 'date' => $booking_date) );
				
				if(!$order) // если в базе нету => не утверждали
					$edit_mode = 1;
				else
					$edit_mode = !(boolean)$order->getCompleted();
			}
		}


		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
				
		$result = array(	'code' => 200,
							'data' => $bookings_array,
							'edit_mode'=>(int)$edit_mode	);

		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}

	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}/order/{booking_date}.{_format}",
	 * 			name="API_OrderItem_create",
	 * 			requirements={	"_method" = "POST",
	 *							"_format" = "json|xml",
	 *							"booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={	"booking_date" = 0,
	 *						"_format" = "json"	})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	public function API_createAction($cid, $rid, $booking_date, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
			
		$restaurants = $restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);

		$available_restaurants = array();
		foreach ($restaurants AS $r)
		{
			$available_restaurants[] = $r->getId();
			if ($r->getId() == $rid)
				$restaurant = $r;
		}

		if (!in_array($rid, $available_restaurants) || !isset($restaurant))
			return new Response('Нет доступа к ресторану', 403, array('Content-Type' => 'application/json'));
		
		if ($booking_date == '0' || $booking_date < date('Y-m-d'))
			$booking_date = date('Y-m-d');

		$company = $restaurant->getCompany();
		
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN'))
		{
			$order = $this->getDoctrine()
						->getRepository('SupplierBundle:Order')
						->findOneBy( array(	'company'=>$cid, 'date' => $booking_date) );
			
			if($order)
				if($order->getCompleted())
					return new Response('Order is completed. You can not create order.', 403, array('Content-Type' => 'application/json'));
		}
		
		$model = (array)json_decode($request->getContent());
		
		if ( count($model) > 0 && isset($model['product']) && isset($model['amount']) )
		{
			if ( $model['amount'] == "0" )
				return new Response('Amount should not be 0', 404, array('Content-Type' => 'application/json'));
			
			
			$product = $this->getDoctrine()
							->getRepository('SupplierBundle:Product')
							->find((int)$model['product']);
									
			if (!$product)
				return new Response('No product found for id '.$pid, 404, array('Content-Type' => 'application/json'));
			
			$model['amount'] = str_replace(',', '.', $model['amount']);
			$amount = 0 + $model['amount'];
		
			$validator = $this->get('validator');
			$booking = new OrderItem();
			$booking->setProduct($product);
			$booking->setDate($booking_date);
			$booking->setAmount($amount);
			$booking->setCompany($company);
			$booking->setRestaurant($restaurant);
			
			$suppliers = $this->getDoctrine()->getRepository('SupplierBundle:Supplier')->findBy(array('company'=>(int)$cid, 'active' =>1));
								
			$suppliers_array = array();
			foreach($suppliers AS $supplier)
				$suppliers_array[] = $supplier->getId();
			
			$best_supplier_offer = $this->getDoctrine()
										->getRepository('SupplierBundle:SupplierProducts')
										->getBestOffer((int)$cid, (int)$product->getId(), $suppliers_array);
			
			if ($best_supplier_offer)
			{
				$booking->setSupplier($best_supplier_offer->getSupplier());
				$booking->setPrice($best_supplier_offer->getPrice());
			}
			else
				return new Response('No supplier found for product #'.$product->getId(), 404, array('Content-Type' => 'application/json'));
			
			$errors = $validator->validate($booking);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();

				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($booking);
				$em->flush();
				
				$result = array(	'code' => 200,
									'data' => array(	'id' => $booking->getId(),
														'company' => $company->getId(), 
														'date' => $booking->getDate(), 
														'amount' => $booking->getAmount(),
														'restaurant' => $restaurant->getId(),
														'product' => $product->getId(),		));
				
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}
	
	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}/order/{booking_date}/{bid}.{_format}", 
	 * 				name="API_OrderItem_delete", 
 	 * 				requirements={	"_method" = "DELETE",
	 *								"_format" = "json|xml",
	 *								"booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={	"booking_date" = 0,
	 *						"_format" = "json"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	 public function API_deleteAction($cid, $rid, $booking_date, $bid)
	 {
		$user = $this->get('security.context')->getToken()->getUser();
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
			
		$restaurants = $restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);

		$available_restaurants = array();
		foreach ($restaurants AS $r)
		{
			$available_restaurants[] = $r->getId();
			if ($r->getId() == $rid)
				$restaurant = $r;
		}

		if (!in_array($rid, $available_restaurants) || !isset($restaurant))
			return new Response('Нет доступа к ресторану', 403, array('Content-Type' => 'application/json'));
		
		if ( !$booking_date > date('Y-m-d') )
			return new Response('Forbidden Restaurant', 403, array('Content-Type' => 'application/json'));
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		if (!$company) 
			return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
		
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN'))
		{
			$order = $this->getDoctrine()
						->getRepository('SupplierBundle:Order')
						->findOneBy( array(	'company'=>$cid, 'date' => $booking_date) );
			
			if($order)
				if($order->getCompleted())
					return new Response('Order is completed. You can not edit order.', 403, array('Content-Type' => 'application/json'));
		}
	
		$booking = $this->getDoctrine()->getRepository('SupplierBundle:OrderItem')->find($bid);
		
		if (!$booking)
		{
			$result = array('code' => 200, 'data' => $bid, 'message' => 'No oreder item found for id '.$rid);
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
		}

		if ($booking->getDate() < date('Y-m-d') )
			return new Response('You can not remove the old booking', 403, array('Content-Type' => 'application/json'));
		else
		{
			$em = $this->getDoctrine()->getEntityManager();				
			$em->remove($booking);
			$em->flush();

			$result = array('code' => 200, 'data' => $bid);
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
		}
	}


	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}/order/{booking_date}/{bid}.{_format}", 
	 * 			name="API_OrderItem_update", 
	 * 			requirements={	"_method" = "PUT",
	 *							"_format" = "json|xml",
	 *							"booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={	"booking_date" = 0,
	 *						"_format" = "json"	})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	public function API_updateAction($cid, $rid, $booking_date, $bid, Request $request)
	{ 
		$user = $this->get('security.context')->getToken()->getUser();
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
			
		$restaurants = $restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);

		$available_restaurants = array();
		foreach ($restaurants AS $r)
		{
			$available_restaurants[] = $r->getId();
			if ($r->getId() == $rid)
				$restaurant = $r;
		}

		if (!in_array($rid, $available_restaurants) || !isset($restaurant))
			return new Response('Нет доступа к ресторану', 403, array('Content-Type' => 'application/json'));
		
		if ($booking_date == '0' || $booking_date < date('Y-m-d'))
			$booking_date = date('Y-m-d');
		
		$model = (array)json_decode($request->getContent());

		if	(	count($model) > 0 && 
				isset($model['id']) && 
				is_numeric($model['id']) && 
				$bid == $model['id'] && 
				isset($model['product']) && 
				isset($model['amount'])	)
		{

			if ( $model['amount'] == "0" ||  $model['amount'] == "")
				return new Response('Amount should not be 0', 404, array('Content-Type' => 'application/json'));
			
			$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
			if (!$company)
				return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
		
			
			if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN'))
			{
				$order = $this->getDoctrine()
							->getRepository('SupplierBundle:Order')
							->findOneBy( array(	'company'=>$cid, 'date' => $booking_date) );
							
				if($order)
					if($order->getCompleted())
						return new Response('Order is completed. You can not edit order.', 403, array('Content-Type' => 'application/json'));
			}
			
			$booking = $this->getDoctrine()
									->getRepository('SupplierBundle:OrderItem')
									->find($bid);
			if (!$booking)
				return new Response('No booking found for id '.$rid, 404, array('Content-Type' => 'application/json'));
			
			if ($booking->getDate() < date('Y-m-d') )
				return new Response('You can not edit the old booking', 403, array('Content-Type' => 'application/json'));
			else
			{
				$model['amount'] = str_replace(',', '.', $model['amount']);
				$amount = 0 + $model['amount'];
				
				$validator = $this->get('validator');
				
				$product = $this->getDoctrine()->getRepository('SupplierBundle:Product')->find((int)$model['product']);
						
				if (!$product)
					return new Response('No product found for id '.$pid, 404, array('Content-Type' => 'application/json'));
								
				$booking->setAmount($amount);
				$booking->setProduct($product);
				
				$suppliers = $this->getDoctrine()->getRepository('SupplierBundle:Supplier')->findBy(array('company'=>(int)$cid, 'active' =>1));
									
				$suppliers_array = array();
				foreach($suppliers AS $supplier)
					$suppliers_array[] = $supplier->getId();
				
				$best_supplier_offer = $this->getDoctrine()
											->getRepository('SupplierBundle:SupplierProducts')
											->getBestOffer((int)$cid, (int)$product->getId(), $suppliers_array);
			
				if ($best_supplier_offer)
				{
					$booking->setSupplier($best_supplier_offer->getSupplier());
					$booking->setPrice($best_supplier_offer->getPrice());
				}
				else
					return new Response('No supplier found for product #'.$product->getId(), 404, array('Content-Type' => 'application/json'));

				$errors = $validator->validate($booking);
				
				if (count($errors) > 0) {
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();
					
					return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
					
				} else {
					
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($booking);
					$em->flush();
					
					$result = array('code'=> 200, 
									'data' => array(	'name' => $booking->getProduct()->getName(),
														'amount' => $booking->getAmount(),
														'product' => $booking->getProduct()->getId(),
														'id' => $booking->getId()	));
					return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
				}
			}
		}
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}
}

