<?php

namespace Acme\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Acme\UserBundle\Entity\WorkingHours;

/**
 * WorkingHours controller.
 */
class WorkingHoursController extends Controller
{
    /**
     * Lists all WorkingHours entities.
     *
     * @Route("company/{cid}/restaurant/{rid}/shift/{date}", name="WorkingHours_list", defaults={"date" = 0})
     * @Route("company/{cid}/restaurant/{rid}/shift/{date}/", name="WorkingHours_list_", defaults={"date" = 0})
     * @Template()
     * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_ADMIN, ROLE_RESTAURANT_DIRECTOR")
     */
    public function listAction($cid, $rid, $date)
    {
		if ($date == '0')
			$date = date('Y-m-d');
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		
		if (!$company)
			throw $this->createNotFoundException('Company not found');
			
			
		$restaurant = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->find($rid);
		
		if (!$restaurant)
			throw $this->createNotFoundException('Restaurant not found');
		
		$agreed = 0;
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR') && $date > date('Y-m-d'))
			$agreed = 1;
	
		$edit_mode = 1;
		if ($this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR') || $date <= date('Y-m-d'))
			$edit_mode = 0;

        return array('company' => $company, 'restaurant' => $restaurant, 'date' => $date, 'edit_mode' => $edit_mode, 'agreed' => $agreed);
    }

    /**
     * @Route(	"api/company/{cid}/restaurant/{rid}/shift/{date}.{_format}", 
     * 			name="API_WorkingHours_list", 
     * 			requirements={	"_method" = "GET",
	 *							"_format" = "json|xml",
	 *							"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={	"date" = 0,
	 *						"_format" = "json"})
     * @Route(	"api/company/{cid}/restaurant/{rid}/shift/{date}.{_format}/", 
     * 			name="API_WorkingHours_list_", 
     * 			requirements={	"_method" = "GET",
     *							"_format" = "json|xml",
	 *							"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={	"date" = 0,
							"_format" = "json"}	)
     * @Route(	"api/company/{cid}/restaurant/{rid}/shift.{_format}/",
	 *			name="API_WorkingHours_list__",
	 *			requirements={"_method" = "GET", "_format" = "json|xml"},
	 *			defaults={"date" = 0, "_format" = "json"})
     * @Template()
     * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_RESTAURANT_DIRECTOR")
     */
    public function API_listAction($cid, $rid, $date, Request $request)
    {
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findOneCompanyOneRestaurant($cid, $rid);
		
		if (!$company)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
		
		if ($date == '0' || $date == 0)
			$date = date('Y-m-d');
		
		$entities = $this->getDoctrine()->getRepository('AcmeUserBundle:WorkingHours')->findBy(array(	'company'		=> (int)$cid, 
																										'restaurant'	=> (int)$rid,
																										'date'			=> $date));

		$entities_array = array();
		if ($entities)
			foreach ($entities AS $p)
				$entities_array[] = array( 	'id'			=> $p->getId(),
											'user'			=> $p->getUser()->getId(),
											'company'		=> $p->getCompany()->getId(),
											'restaurant'	=> $p->getRestaurant()->getId(),
											'duty'			=> $p->getDuty()->getId(),
											'planhours'		=> $p->getPlanhours(),
											'facthours'		=> $p->getFacthours(),
											'agreed'		=> ($p->getAgreed() || $date <= date('Y-m-d'))?1:0,
											'date'			=> $p->getDate(),
											);
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
				
		$result = array('code' => 200, 'data' => $entities_array);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	
    /**
     * @Route(	"api/company/{cid}/restaurant/{rid}/shift/{date}.{_format}", 
     * 			name="API_WorkingHours_create", 
     * 			requirements={	"_method" = "POST",
	 *							"_format" = "json|xml",
	 *							"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={	"_format" = "json",
							"date" = 0 })
     * @Template()
     * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN")
     */
    public function API_createAction($cid, $rid, $date, Request $request)
    {
		$restaurant = $this->getDoctrine()
						->getRepository('SupplierBundle:Restaurant')
						->findOneByIdJoinedToCompany($rid, $cid);

		if (!$restaurant)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
		
		$company = $restaurant->getCompany();
		
		if ($date == '0')
			$date = date('Y-m-d');
			

		if ($date < date('Y-m-d') )
			return new Response('Запрещено редактировать старые смены', 403, array('Content-Type' => 'application/json'));
		
		$model = (array)json_decode($request->getContent());
		
		if ( count($model) > 0 && isset($model['user']) && isset($model['duty']) )
		{
			$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->find((int)$model['user']);
									
			if (!$user)
				return new Response('No user found for id '.(int)$model['user'], 404, array('Content-Type' => 'application/json'));
				
			$duty = $this->getDoctrine()->getRepository('AcmeUserBundle:Duty')->find((int)$model['duty']);
									
			if (!$duty)
				return new Response('No duty found for id '.(int)$model['duty'], 404, array('Content-Type' => 'application/json'));
			
			$new_row = new WorkingHours();
			$new_row->setCompany($company);
			$new_row->setRestaurant($restaurant);
			$new_row->setUser($user);
			$new_row->setDuty($duty);
			$new_row->setDate($date);
			$new_row->setPlanhours((int)$model['planhours']);
			$new_row->setFacthours((int)$model['facthours']);
			
			$validator = $this->get('validator');
			$errors = $validator->validate($new_row);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();

				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($new_row);
				$em->flush();
				
				$result = array(	'code' => 200,
									'data' => array(	'id'			=> $new_row->getId(),
														'company'		=> $company->getId(),
														'restaurant'	=> $restaurant->getId(),
														'user'			=> $user->getId(),
														'duty'			=> $duty->getId(),
														'date' 			=> $new_row->getDate(), 
														'planhours'		=> $new_row->getPlanhours(),
														'facthours'		=> $new_row->getFacthours(),
														'agreed'		=> 0	));
				
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		else
			return new Response('Некорректный запрос', 400, array('Content-Type' => 'application/json'));
	}
	
	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}/shift/{date}/{sid}.{_format}", 
	 * 				name="API_WorkingHours_delete", 
 	 * 				requirements={	"_method" = "DELETE",
									"_format" = "json|xml",
									"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={	"date" = 0,
							"_format" = "json"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	public function API_deleteAction($cid, $rid, $date, $sid)
	{
		$restaurant = $this->getDoctrine()
						->getRepository('SupplierBundle:Restaurant')
						->findOneByIdJoinedToCompany($rid, $cid);

		if (!$restaurant)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
		
		if ($date == '0' || $date == 0) 
			$date = date('Y-m-d');
			
		$row = $this->getDoctrine()->getRepository('AcmeUserBundle:WorkingHours')->find($sid);
		
		if (!$row)
		{
			$result = array('code' => 200, 'data' => $sid, 'message' => 'Смена не найдена');
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
		}

		if ($row->getDate() < date('Y-m-d') )
			return new Response('Запрещено редактировать старые смены', 403, array('Content-Type' => 'application/json'));
		else
		{
			$em = $this->getDoctrine()->getEntityManager();				
			$em->remove($row);
			$em->flush();

			$result = array('code' => 200, 'data' => $sid);
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
		}
	}
	
	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}/shift/{date}/{sid}.{_format}", 
	 * 			name="API_WorkingHours_update", 
	 * 			requirements={	"_method" = "PUT",
	 *							"_format" = "json|xml",
	 *							"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$",
	 *							"sid" = "\d*"},
	 *			defaults={	"date" = 0,
							"_format" = "json"	})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	public function API_updateAction($cid, $rid, $date, $sid, Request $request)
	{
		$restaurant = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->findOneByIdJoinedToCompany($rid, $cid);

		if (!$restaurant)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
		
		$company = $restaurant->getCompany();
		
		if ($date == '0' || $date == 0)
			$date = date('Y-m-d');
			
		$row = $this->getDoctrine()->getRepository('AcmeUserBundle:WorkingHours')->find($sid);
		
		if (!$row)
			return new Response('Не найден элемент #'.$sid, 404, array('Content-Type' => 'application/json'));

		if ($row->getDate() < date('Y-m-d') )
			return new Response('Запрещено редактировать старые смены', 403, array('Content-Type' => 'application/json'));
		else
		{
			$model = (array)json_decode($request->getContent());

			if	(	count($model) > 0 && 
					isset($model['user']) && 
					isset($model['duty']) && 
					is_numeric($model['id']) && $sid == $model['id'] && 
					isset($model['planhours']) &&
					isset($model['facthours'])	)
			{
				$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->find((int)$model['user']);
										
				if (!$user)
					return new Response('No user found for id '.(int)$model['user'], 404, array('Content-Type' => 'application/json'));
					
				$duty = $this->getDoctrine()->getRepository('AcmeUserBundle:Duty')->find((int)$model['duty']);
										
				if (!$duty)
					return new Response('No duty found for id '.(int)$model['duty'], 404, array('Content-Type' => 'application/json'));
					
				$row->setUser($user);
				$row->setDuty($duty);
				$row->setPlanhours((int)$model['planhours']);
				$row->setFacthours((int)$model['facthours']);
				
				$validator = $this->get('validator');
				$errors = $validator->validate($row);
				
				if (count($errors) > 0) {
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();
					
					return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
					
				} else {
					
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($row);
					$em->flush();
				
					$result = array(	'code' => 200,
										'data' => array(	'id'			=> $row->getId(),
															'company'		=> $company->getId(),
															'restaurant'	=> $restaurant->getId(),
															'user'			=> $user->getId(),
															'duty'			=> $duty->getId(),
															'date' 			=> $row->getDate(), 
															'planhours'		=> $row->getPlanhours(),
															'facthours'		=> $row->getFacthours(),
															'agreed'		=> 0	));
					
					return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
				}
			}
			else
				return new Response('Некорректный запрос', 400, array('Content-Type' => 'application/json'));
		}
    }
    
	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}/shift/{date}/agreed.{_format}", 
	 * 			name="API_WorkingHours_agree", 
	 * 			requirements={	"_method" = "PUT",
	 *							"_format" = "json|xml",
	 *							"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={	"date" = 0,
							"_format" = "json"	})
	 * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_DIRECTOR")
	 */
    public function API_agreedAction($cid, $rid, $date, Request $request)
    {
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findOneCompanyOneRestaurant($cid, $rid);
		
		if (!$company)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
	
		$entities = $this->getDoctrine()->getRepository('AcmeUserBundle:WorkingHours')->findBy(array(	'company'		=> (int)$cid, 
																										'restaurant'	=> (int)$rid,
																										'date'			=> $date));
		$agreed_working_hourse = array();

		$entities_array = array();
		if ($entities)
			foreach ($entities AS $p)
			{
				$p->setAgreed(1);
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($p);
				$em->flush();
				
				$agreed_working_hourse[] = $p->getId();
			}
		else
			return new Response('У вас нет смены на это число', 404, array('Content-Type' => 'application/json'));
		
		
		$result = array(	'code' => 200,
							'data' => $agreed_working_hourse);
		
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
    
	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}/shift/{date}/disagreed.{_format}", 
	 * 			name="API_WorkingHours_disagree", 
	 * 			requirements={	"_method" = "PUT",
	 *							"_format" = "json|xml",
	 *							"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={	"date" = 0,
							"_format" = "json"	})
	 * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_DIRECTOR")
	 */
    public function API_disagreedAction($cid, $rid, $date, Request $request)
    {
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findOneCompanyOneRestaurant($cid, $rid);
		
		if (!$company)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
	
		$entities = $this->getDoctrine()->getRepository('AcmeUserBundle:WorkingHours')->findBy(array(	'company'		=> (int)$cid, 
																										'restaurant'	=> (int)$rid,
																										'date'			=> $date));
		$agreed_working_hourse = array();

		$entities_array = array();
		if ($entities)
			foreach ($entities AS $p)
			{
				$p->setAgreed(0);
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($p);
				$em->flush();
				
				$agreed_working_hourse[] = $p->getId();
			}
		else
			return new Response('У вас нет смены на это число', 404, array('Content-Type' => 'application/json'));
		
		
		$result = array(	'code' => 200,
							'data' => $agreed_working_hourse);
		
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}

}
