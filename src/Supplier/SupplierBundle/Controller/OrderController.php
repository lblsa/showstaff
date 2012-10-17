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
     * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
     */
    public function listAction($cid, $booking_date, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();
				
		$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

		if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
		{
			if ($request->isXmlHttpRequest()) 
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden Company');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			} else {
				throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		
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
							$restaurants_array[] = array(	'id'	=> $p->getId(),
															'name'	=> $p->getName(),
															'company'		=> (int)$cid	);
		
		$products = $company->getProducts();
		$products_array = array();
		if ($products)
			foreach ($products AS $p)
				$products_array[] = array(	'id' => $p->getId(),
											'name'=> $p->getName(), 
											'unit' => $p->getUnit()->getId(),
											'use' => 0 );
											
		$suppler_products = $this->getDoctrine()
								->getRepository('SupplierBundle:SupplierProducts')
								->findByCompany($cid);
		$suppler_products_array = array();
		if ($suppler_products)
			foreach ($suppler_products AS $p)
			{		
				$suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()] = $p->getPrice();
				$suppler_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()] = $p->getSupplierName();
			}
		
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
											'unit' => $p->getProduct()->getUnit()->getId(),
											'supplier_name' => isset($suppler_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$suppler_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0,
											'price' => isset($suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0);
			}								
		
		
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $bookings_array);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
			header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");// HTTP/1.0
			$response->sendContent();
			die(); 
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
				
		$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

		if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
		{
			if ($request->isXmlHttpRequest()) 
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden Company');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			} else {
				throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
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

    /**
     * @Route("	company/{cid}/order/export/{booking_date}", 
     * 			name="export_order", 
     * 			requirements={"_method" = "GET", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0} )
     * @Template()
     * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
     */
	public function exportAction($cid, $booking_date, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
				
		$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());

		if (!$permission || $permission->getCompany()->getId() != $cid) // проверим из какой компании
		{
			if ($request->isXmlHttpRequest()) 
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden Company');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			} else {
				throw new AccessDeniedHttpException('Forbidden Company');
			}
		}
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
		if (!$company) {
			$code = 404;
			$result = array('code' => $code, 'message' => 'No restaurant found for id '.$rid.' in company #'.$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
	
        // ask the service for a Excel5
        $excelService = $this->get('xls.service_xls5');
        // or $this->get('xls.service_pdf');
        // or create your own is easy just modify services.yml


        // create the object see http://phpexcel.codeplex.com documentation
        $excelService->excelObj->getProperties()->setCreator("Maarten Balliauw")
                            ->setLastModifiedBy("Maarten Balliauw")
                            ->setTitle("Office 2005 XLSX Test Document")
                            ->setSubject("Office 2005 XLSX Test Document")
                            ->setDescription("Test document for Office 2005 XLSX, generated using PHP classes.")
                            ->setKeywords("office 2005 openxml php")
                            ->setCategory("Test result file");
        $excelService->excelObj->setActiveSheetIndex(0)
                    ->setCellValue('A1', 'Hello')
                    ->setCellValue('A2', 'Viola')
                    ->setCellValue('B2', 'world!');
        $excelService->excelObj->getActiveSheet()->setTitle('Simple');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $excelService->excelObj->setActiveSheetIndex(0);

        //create the response
        $response = $excelService->getResponse();
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=stdream2.xls');

        // If you are using a https connection, you have to set those two headers for compatibility with IE <9
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        return $response;   
	}
}
