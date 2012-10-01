<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Supplier\SupplierBundle\Entity\Company;
use Supplier\SupplierBundle\Entity\Restaurant;
use Supplier\SupplierBundle\Entity\Order;
use Supplier\SupplierBundle\Entity\OrderItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;

class OrderController extends Controller
{
	
    /**
     * @Route("	company/{cid}/order/{booking_date}", 
     * 			name="Order_list", 
     * 			requirements={"_method" = "GET", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0} )
     * @Template()
     * @Secure(roles="ROLE_ORDER_MANAGER")
     */
    public function listAction($cid, $booking_date, Request $request)
    {
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
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
				throw $this->createNotFoundException('No company found for id '.$cid);
			}
		}
		
		$suppliers = $this->getDoctrine()
						->getRepository('SupplierBundle:Supplier')
						->findByCompany($cid);

		
		$suppliers_array = array();
				if ($suppliers)
					foreach ($suppliers AS $p)
							$suppliers_array[] = array(	'id' => $p->getId(),
														'name'=> $p->getName()	);
		
		$restaurants = $company->getRestaurants();
		$restaurants_array = array();
				if ($restaurants)
					foreach ($restaurants AS $p)
							$restaurants_array[] = array(	'id' => $p->getId(),
															'name'=> $p->getName()	);
		
		$products = $company->getProducts();
		$products_array = array();
		if ($products)
			foreach ($products AS $p)
				$products_array[] = array(	'id' => $p->getId(),
											'name'=> $p->getName(), 
											'unit' => $p->getUnit(),
											'use' => 0 );
											
		$suppler_products = $this->getDoctrine()
								->getRepository('SupplierBundle:SupplierProducts')
								->findByCompany($cid);
		$suppler_products_array = array();
		if ($suppler_products)
			foreach ($suppler_products AS $p)				
				$suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()] = $p->getPrice();
		
		
		$bookings = $this->getDoctrine()->getRepository('SupplierBundle:OrderItem')
										->findBy( array(	'company'=>$cid, 'date' => $booking_date) );
										
		$order = $this->getDoctrine()
						->getRepository('SupplierBundle:Order')
						->findOneBy( array(	'company'=>$cid, 'date' => date('Y-m-d')) );
		
		if(!$order)
			$completed = 0;
		else
		{
			$completed = (int)$order->getCompleted();
		}
										
		$bookings_array = array();
		if ($bookings)
			foreach ($bookings AS $p)
			{	
				$bookings_array[] = array(	'id' => $p->getId(),
											'amount' => $p->getAmount(),
											'product' => $p->getProduct()->getId(),
											'restaurant' => $p->getRestaurant()->getId(),
											'supplier' => $p->getSupplier()->getId(),
											'name' => $p->getProduct()->getName(),	
											'price' => isset($suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0);
			}								
		
		return array(	'company' => $company,
						'restaurants_json' => json_encode($restaurants_array), 
						'suppliers_json' => json_encode($suppliers_array), 
						'bookings_json' => json_encode($bookings_array),
						'products_json' => json_encode($products_array),
						'booking_date' => $booking_date,
						'completed' => $completed,
						'edit_mode' => $booking_date<date('Y-m-d')?false:true );
	}
	
	
    /**
     * @Route("	company/{cid}/order/{booking_date}", 
     * 			name="Order_list_save", 
     * 			requirements={"_method" = "PUT", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0} )
     * @Template()
     * @Secure(roles="ROLE_ORDER_MANAGER")
     */
	public function saveAction($cid, $booking_date, Request $request)
	{
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
		if (!$company) {
			$code = 404;
			$result = array('code' => $code, 'message' => 'No restaurant found for id '.$rid.' in company #'.$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}

		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['completed']) && is_numeric($model['completed']))
		{
			$order = $this->getDoctrine()
						->getRepository('SupplierBundle:Order')
						->findOneBy( array(	'company'=>$cid, 'date' => date('Y-m-d')) );
			
			
			
			if ($order)
			{
				$order->setCompleted((bool)$model['completed']);
			}
			else
			{
				$order = new Order();
				$order->setCompany($company);
				$order->setCompleted((int)$model['completed']);
				$order->setDate(date('Y-m-d'));
			}
			
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($order);
			$em->flush();
			
			$code = 200;
			
			$result = array('code'		=> $code,
							'message'	=> ((int)$model['completed']==1)?'Заказ сформирован и закрыт для редактирования':'Заказ открыт для редактирования',
							'data' 		=> array(	'company' => $order->getCompany()->getId(),
													'completed' => $order->getCompleted(),
													'date' => $order->getDate() ));
													
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
		$code = 400;
		$result = array('code'=>$code, 'message'=> 'Invalid request');
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	}
}
