<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Supplier\SupplierBundle\Entity\Company;
use Supplier\SupplierBundle\Entity\Restaurant;
use Supplier\SupplierBundle\Entity\Order;
use Supplier\SupplierBundle\Entity\OrderItem;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
     * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
     */
    public function listAction($cid, $booking_date, Request $request)
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
		
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest()) 
				return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
			else
				throw $this->createNotFoundException('No company found for id '.$cid);
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
							$restaurants_array[] = array(	'id'	=> $p->getId(),
															'name'	=> $p->getName(),
															'company'		=> (int)$cid	);
		
		$products = $company->getProducts();
		$products_array = array();
		
		if ($products)
		{
			foreach ($products AS $p)
			{
				if ($booking_date < date('Y-m-d') || $p->getActive())
				{
					$products_array[] = array(	'id' => $p->getId(),
												'name'=> $p->getName(), 
												'unit' => $p->getUnit()->getId(),
												'use' => 0 );
				}
			}
		}
											
		$suppler_products = $this->getDoctrine()
								->getRepository('SupplierBundle:SupplierProducts')
								->findByCompany($cid);
		$suppler_products_array = array();
		if ($suppler_products)
		{
			foreach ($suppler_products AS $p)
			{	
				if ($booking_date < date('Y-m-d') || $p->getSupplier()->getActive())
				{
					$suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()] = $p->getPrice();
					$suppler_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()] = $p->getSupplierName()?$p->getSupplierName():$p->getProduct()->getName();
				}
			}
		}
		$bookings = $this->getDoctrine()->getRepository('SupplierBundle:OrderItem')->findBy( array(	'company'=>$cid, 'date' => $booking_date) );
										
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
				if ($booking_date < date('Y-m-d') || ($p->getProduct()->getActive() && $p->getSupplier()->getActive()))
				{
					$bookings_array[] = array(	'id' => $p->getId(),
												'amount' => $p->getAmount(),
												'product' => $p->getProduct()->getId(),
												'restaurant' => $p->getRestaurant()->getId(),
												'supplier' => $p->getSupplier()->getId(),
												'name' => $p->getProduct()->getName(),
												'unit' => $p->getProduct()->getUnit()->getId(),
												'supplier_name' => isset($suppler_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$suppler_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0,
												'price' => isset($suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0);
				}
			}
										
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0		
		
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $bookings_array);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));

			return $response;
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
     * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
     */
	public function saveAction($cid, $booking_date, Request $request)
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
		
		if ($booking_date != '0' && $booking_date < date('Y-m-d'))
			return new Response('Нельзя редактировать заказы прошлых дней', 403, array('Content-Type' => 'application/json'));
		
		if ($booking_date == '0' || $booking_date < date('Y-m-d'))
			$booking_date = date('Y-m-d');
		
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
		if (!$company)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));

		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['completed']) && is_numeric($model['completed']))
		{						
			if (0)  //перепроверка перед сформированием заказа, пока не нужно
			{
				$order_items = $this->getDoctrine()
							->getRepository('SupplierBundle:OrderItem')
							->findBy( array(	'company'=>$cid, 'date' => $booking_date) );
				$suppliers = $this->getDoctrine()
						->getRepository('SupplierBundle:Supplier')
						->findBy(array('company'=>(int)$cid, 'active' =>1));
				$suppliers_array = array();
				foreach($suppliers AS $supplier)
					$suppliers_array[] = $supplier->getId();
				
				$em = $this->getDoctrine()->getEntityManager();
				foreach ($order_items AS $p)
					if ($p->getProduct()->getActive())
					{
						$best_supplier_offer = $this->getDoctrine()->getRepository('SupplierBundle:SupplierProducts')->getBestOffer((int)$cid, (int)$p->getProduct()->getId(), $suppliers_array);
				
						if ($best_supplier_offer) // заново выставим цены и поставщика
						{
							$p->setPrice($best_supplier_offer->getPrice());
							$p->setSupplier($best_supplier_offer);
							
							$em->persist($p);
							$em->flush();
						} else {
							$em->remove($p);
							$em->flush();
						}
					} else {
						$em->remove($p);
						$em->flush();
					}
			}
			
			$order = $this->getDoctrine()
						->getRepository('SupplierBundle:Order')
						->findOneBy( array(	'company'=>$cid, 'date' => $booking_date) );

			
			if ($order)
			{
				$order->setCompleted((bool)$model['completed']);
			}
			else
			{
				$order = new Order();
				$order->setCompany($company);
				$order->setCompleted((int)$model['completed']);
				$order->setDate($booking_date);
			}
			
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($order);
			$em->flush();
			
			$code = 201;
			
			$result = array('code'		=> $code,
							'message'	=> ((int)$model['completed']==1)?'Заказ сформирован и закрыт для редактирования':'Заказ открыт для редактирования',
							'data' 		=> array(	'company' => $order->getCompany()->getId(),
													'completed' => $order->getCompleted(),
													'date' => $order->getDate() ));
													
			return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		}

		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}

    /**
     * @Route("/company/{cid}/order/export/{booking_date}", name="export_order", 
     * 			requirements={"_method" = "GET", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0} )
     * @Route("/company/{cid}/order/export/{booking_date}/", name="export_order_", 
     * 			requirements={"_method" = "GET", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0} )
     * @Route("/company/{cid}/order/export/", name="export_order__", requirements={"_method" = "GET"}, defaults={"booking_date" = 0} )
     * @Template()
     * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
     */
	public function exportAction($cid, $booking_date, Request $request)
	{
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');
		
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
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
		if (!$company) {
			if ($request->isXmlHttpRequest())
				return new Response('No found company #'.$cid, 404, array('Content-Type' => 'application/json'));
			else
				throw $this->createNotFoundException('No found company #'.$cid);
		}
		
		
		$supplier_products = $this->getDoctrine()
								->getRepository('SupplierBundle:SupplierProducts')
								->findByCompany($cid);
		$supplier_products_array = array();
		if ($supplier_products)
			foreach ($supplier_products AS $p)
			{		
				$supplier_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()] = $p->getPrice();
				$supplier_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()] = $p->getSupplierName()?$p->getSupplierName():$p->getProduct()->getName();
			}
		
		
		
		$bookings = $this->getDoctrine()->getRepository('SupplierBundle:OrderItem')
										->findBy( array(	'company'=>$cid, 'date' => $booking_date) );
		$bookings_array = array();
		if ($bookings)
		{
			foreach ($bookings AS $p)
			{
				$bookings_array[$p->getSupplier()->getName()][] = array(	'id' => $p->getId(),
											'amount' => $p->getAmount(),
											'product' => $p->getProduct()->getName(),
											'restaurant' => $p->getRestaurant()->getName(),
											'supplier' => $p->getSupplier()->getName(),
											'name' => $p->getProduct()->getName(),
											'unit' => $p->getProduct()->getUnit()->getName(),
											'supplier_name' => isset($supplier_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$supplier_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0,
											'price' => isset($supplier_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$supplier_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0);
			}
		}
	
        // ask the service for a Excel5
        $excelService = $this->get('xls.service_xls5');
        // or $this->get('xls.service_pdf');
        // or create your own is easy just modify services.yml

        // create the object see http://phpexcel.codeplex.com documentation
        $excelService->excelObj->getProperties()->setCreator($user->getUsername())
                            ->setLastModifiedBy($user->getUsername())
                            ->setTitle("Заказ компании \"".$company->getName()."\" на ".$booking_date." число")
                            ->setSubject("Заказ компании \"".$company->getName()."\" на ".$booking_date." число")
                            ->setDescription("Test document for Office 2005 XLSX, generated using PHP classes.")
                            ->setKeywords("Order")
                            ->setCategory("Order result file");
        $sheet = $excelService->excelObj->setActiveSheetIndex(0);

		$sheet->setCellValue('A1', 'Название продукта поставщика');
		$sheet->setCellValue('B1', 'Количество');
		$sheet->setCellValue('C1', 'Единица измерения');
		$sheet->setCellValue('D1', 'Цена');
		$sheet->setCellValue('E1', 'Ресторан');
        
        $i = 2;
        foreach ($bookings_array AS $k=>$v)
        {
			$sheet->setCellValue('A'.$i++, $k);
			foreach ($v AS $b)
			{
				$sheet->setCellValue('A'.$i, $b['supplier_name']);
				$sheet->setCellValue('B'.$i, $b['amount']);
				$sheet->setCellValue('C'.$i, $b['unit']);
				$sheet->setCellValue('D'.$i, $b['price']);
				$sheet->setCellValue('E'.$i, $b['restaurant']);
				$i++;
			}
			$i++;
		}
        //$excelService->excelObj->setCellValue('B2', '3world!');
        $excelService->excelObj->getActiveSheet()->setTitle('Order '.$booking_date);
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $excelService->excelObj->setActiveSheetIndex(0);

        //create the response
        $response = $excelService->getResponse();
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=Order '.$booking_date.'.xls');

        // If you are using a https connection, you have to set those two headers for compatibility with IE <9
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        return $response;   
	}
}
