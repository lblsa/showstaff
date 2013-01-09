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
     * @Route(	"company/{cid}/order/{booking_date}", 
     * 			name="Order_list", 
     * 			requirements={"_method" = "GET", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0} )
     * @Template()
     * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
     */
    public function listAction($cid, $booking_date, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();

		$restaurants_list = array();
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				throw new AccessDeniedHttpException('Нет доступа к компании');

		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
		
		if ($restaurants)
			foreach ($restaurants as $r)
				$restaurants_list[$r->getId()] = $r->getName();
		
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
		if (!$company)
			throw $this->createNotFoundException('No company found for id '.$cid);
		
		$restaurants = $company->getRestaurants();
		$restaurants_array = array();
		
		if ($restaurants)
			foreach ($restaurants AS $p)
					$restaurants_array[] = array(	'id'	=> $p->getId(),
													'name'	=> $p->getName(),
													'company'		=> (int)$cid	);
										
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0		

		return array(	'company' => $company,
						'booking_date' => $booking_date,
						'restaurants_list' => $restaurants_list,);
	}
	
    /**
     * @Route("api/company/{cid}/order/{booking_date}.{_format}", 
     * 			name="API_Order_list", 
     * 			requirements={	"_method" = "GET",
	 *							"_format" = "json|xml",
	 *							"booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={	"booking_date" = 0,
	 *						"_format" = "json"} )
     * @Template()
     * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
     */
    public function API_listAction($cid, $booking_date, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
		if (!$company)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
		
		$order = $this->getDoctrine()->getRepository('SupplierBundle:Order')->findOneBy( array(	'company'=>$cid, 'date' => $booking_date) );
		
		if(!$order)
			$completed = 0;
		else
			$completed = (int)$order->getCompleted();

		$suppliers = $this->getDoctrine()->getRepository('SupplierBundle:Supplier')->findBy(array('company'=>(int)$cid, 'active' =>1));

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
											
		$suppler_products = $this->getDoctrine()->getRepository('SupplierBundle:SupplierProducts')->findByCompany($cid);
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
										
		$bookings_array = array();
		if ($bookings)
			foreach ($bookings AS $p)
				if ($booking_date < date('Y-m-d') || ($p->getProduct()->getActive() && $p->getSupplier()->getActive()))
					$bookings_array[] = array(	'id' => $p->getId(),
												'amount' => $p->getAmount(),
												'product' => $p->getProduct()->getId(),
												'restaurant' => $p->getRestaurant()->getId(),
												'supplier' => $p->getSupplier()->getId(),
												'name' => $p->getProduct()->getName(),
												'unit' => $p->getProduct()->getUnit()->getId(),
												'supplier_name' => isset($suppler_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$suppler_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0,
												'price' => isset($suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$suppler_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0);
		
		$edit_mode = 0;	
		if ($this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN'))
			$edit_mode = 1;
		else
		{
			if ($booking_date > date('Y-m-d') && $completed == 0)
				$edit_mode = 1;
		}
						
 		$completed_mode = 0;
 		if ($this->get('security.context')->isGranted('ROLE_ORDER_MANAGER') && $booking_date > date('Y-m-d'))
 			$completed_mode = 1;

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0		

		$result = array(	'code' => 200, 
							'data' => $bookings_array, 
							'completed' => $completed, 
							'edit_mode' => $edit_mode, 
							'completed_mode' => $completed_mode);

		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	
	
    /**
     * @Route(	"api/company/{cid}/order/{booking_date}.{_format}", 
     * 			name="Order_list_save", 
     * 			requirements={	"_method" = "PUT",
	 *							"_format" = "json|xml",
	 *							"booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={	"booking_date" = 0,
	 *						"_format" = "json"} )
     * @Template()
     * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
     */
	public function API_saveAction($cid, $booking_date, Request $request)
	{		
		$user = $this->get('security.context')->getToken()->getUser();

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
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
				$order->setCompleted((bool)$model['completed']);
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
			
			$result = array('code'		=> 200,
							'message'	=> ((int)$model['completed']==1)?'Заказ сформирован и закрыт для редактирования':'Заказ открыт для редактирования',
							'data' 		=> array(	'company' => $order->getCompany()->getId(),
													'completed' => $order->getCompleted(),
													'date' => $order->getDate() ));
													
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
		}

		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}

    /**
     * @Route("/company/{cid}/order/export/{booking_date}/{type}/{list}", name="export_order", 
     * 			requirements={"_method" = "GET", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0} )
     * @Route("/company/{cid}/order/export/{booking_date}/{type}/{list}/", name="export_order_", 
     * 			requirements={"_method" = "GET", "booking_date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={"booking_date" = 0} )
     * @Template()
     * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN")
     */
	public function exportAction($cid, $booking_date, $type, $list, Request $request)
	{
		if ($booking_date == '0')
			$booking_date = date('Y-m-d');
		
		$user = $this->get('security.context')->getToken()->getUser();
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
		if (!$company) throw $this->createNotFoundException('No found company #'.$cid);

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				throw new AccessDeniedHttpException('Нет доступа к компании');
		
		
		$supplier_products = $this->getDoctrine()->getRepository('SupplierBundle:SupplierProducts')->findByCompany($cid);
								
		$supplier_products_array = array();
		if ($supplier_products)
			foreach ($supplier_products AS $p)
			{		
				$supplier_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()] = $p->getPrice();
				$supplier_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()] = $p->getSupplierName()?$p->getSupplierName():$p->getProduct()->getName();
			}
		
		
		if ($type == 'restaurant')
		{
			$query = $this->getDoctrine()->getEntityManager()
			          ->createQuery('SELECT p FROM SupplierBundle:OrderItem p WHERE p.company = :cid AND p.date = :booking_date AND p.restaurant IN (:restaurant)')
			          ->setParameters( array(	'booking_date'  => $booking_date,
			                      				'cid'       	=> $cid,
			                      				'restaurant'    => explode('-', $list)
			                      			));
		}
		/*$bookings = $this->getDoctrine()->getRepository('SupplierBundle:OrderItem')
											->findBy( array(	'company'=>$cid,
																'date' => $booking_date,
																'restaurant'=>explode('-',$list)) ); */
		if ($type == 'supplier')
		{
			$query = $this->getDoctrine()->getEntityManager()
			          ->createQuery('SELECT p FROM SupplierBundle:OrderItem p WHERE p.company = :cid AND p.date = :booking_date AND p.supplier IN (:supplier)')
			          ->setParameters( array(	'booking_date'  => $booking_date,
			                      				'cid'       	=> $cid,
			                      				'supplier'    	=> explode('-', $list)
			                      			));
		}



		$bookings = $query->getResult();

		$bookings_array = array();
		if ($bookings)
		{
			foreach ($bookings AS $p)
			{
				if ($p->getProduct()->getActive() && $p->getSupplier()->getActive())
				{
					if ($type == 'supplier')
					{
						$bookings_array[ $p->getSupplier()->getName() ][] = array(	
							'id' => $p->getId(),
							'amount' => $p->getAmount(),
							'product' => $p->getProduct()->getName(),
							'restaurant' => $p->getRestaurant()->getName(),
							'supplier' => $p->getSupplier()->getName(),
							'name' => $p->getProduct()->getName(),
							'unit' => $p->getProduct()->getUnit()->getName(),
							'supplier_name' => isset($supplier_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$supplier_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0,
							'price' => isset($supplier_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$supplier_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0
						);
					}
					elseif ($type == 'restaurant')
					{
						$bookings_array[ $p->getRestaurant()->getName() ][] = array(	
							'id' => $p->getId(),
							'amount' => $p->getAmount(),
							'product' => $p->getProduct()->getName(),
							'restaurant' => $p->getRestaurant()->getName(),
							'supplier' => $p->getSupplier()->getName(),
							'name' => $p->getProduct()->getName(),
							'unit' => $p->getProduct()->getUnit()->getName(),
							'supplier_name' => isset($supplier_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$supplier_products_name_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0,
							'price' => isset($supplier_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()])?$supplier_products_array[$p->getProduct()->getId()][$p->getSupplier()->getId()]:0
						);

					}
				}
			}
		}
	
		
		// echo '<pre>'; var_dump($query->getSql());	var_dump($query->getParameters()); print_r($bookings_array); //print_r($supplier_products_array); print_r($supplier_products_name_array); die;
		
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
		if ($type == 'supplier')
			$sheet->setCellValue('E1', 'Ресторан');
        elseif ($type == 'restaurant')
        	$sheet->setCellValue('E1', 'Поставщик');
        
        $i = 2;
        foreach ($bookings_array AS $k=>$v)
        {
			if ($type == 'supplier')
			{
				$sheet->setCellValue('A'.$i++, 'Поставщик: '.$k);
				foreach ($v AS $b)
				{
					$sheet->setCellValue('A'.$i, $b['supplier_name']);
					$sheet->setCellValue('B'.$i, $b['amount']);
					$sheet->setCellValue('C'.$i, $b['unit']);
					$sheet->setCellValue('D'.$i, $b['price']);
					$sheet->setCellValue('E'.$i, $b['restaurant']);
					$i++;
				}
			}
			elseif ($type == 'restaurant')
			{
				$sheet->setCellValue('A'.$i++, 'Ресторан: '.$k);
				foreach ($v AS $b)
				{
					$sheet->setCellValue('A'.$i, $b['supplier_name']);
					$sheet->setCellValue('B'.$i, $b['amount']);
					$sheet->setCellValue('C'.$i, $b['unit']);
					$sheet->setCellValue('D'.$i, $b['price']);
					$sheet->setCellValue('E'.$i, $b['supplier']);
					$i++;
				}
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
