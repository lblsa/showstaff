<?php
/*
план на завтра и дальше для неутвержденного - для менеджеров
план на завтра и дальше для утвержденного - для директоров
план в любой момент - для управляющих
факт только сегодня до 14 - для менеджеров и директоров
факт в любой момент - для управляющих
утверждение плана не влияет на возможность ввести факт
*/
namespace Acme\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\HttpFoundation\Response;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Acme\UserBundle\Entity\WorkingHours;
use Acme\UserBundle\Entity\Shift;

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
    	$user = $this->get('security.context')->getToken()->getUser();

		if ($date == '0')
			$date = date('Y-m-d');
		
		$restaurants_list = array();
		// check exist this company
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		if (!$company)
			throw $this->createNotFoundException('Компания не найдена');

		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				throw new AccessDeniedHttpException('Нет доступа к компании');

		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);

		$available_restaurants = array();
		foreach ($restaurants AS $r)
		{
			$available_restaurants[] = $r->getId();
			$restaurants_list[$r->getId()] = $r->getName();
			if ($r->getId() == $rid)
				$restaurant = $r;
		}
			
		if (!in_array($rid, $available_restaurants) || !isset($restaurant))
			throw new AccessDeniedHttpException('Нет доступа к ресторану');

		$agreed = 0;

		$edit_mode = 0;
		//план на завтра и дальше для утвержденного и неутвержденного - для менеджеров или директора
		if (	(
					$this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR') || $this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN')
				) && $date > date('Y-m-d')
			)
			$edit_mode = 1;
			
		// факт только для менеджеров и директоров сегодня до 14
		if (	(
					$this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') || $this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR')
				) && $date == date('Y-m-d') && date('H')<14
			)
		{
			$edit_mode = 2; //редактируем только фактические часы
			$agreed = 1;
		}

		//для управляющего все всегда можно редактировать
		if ($this->get('security.context')->isGranted('ROLE_ADMIN'))
			$edit_mode = 1;

        return array(	'company' => $company,
        				'restaurant' => $restaurant,
        				'restaurants_list' => $restaurants_list,
        				'date' => $date,
        				'edit_mode' => $edit_mode,
        				'agreed' => $agreed);
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
	 *						"_format" = "json"}	)
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
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
		
		foreach ($restaurants AS $r)
			if ($r->getId() == $rid)
				$restaurant = $r;

		if (!isset($restaurant))
			return new Response('Нет доступа к ресторану', 403, array('Content-Type' => 'application/json'));

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
											'planhours'		=> $p->getPlanhours(),
											'facthours'		=> $p->getFacthours(),
											'description'	=> $p->getDescription(),
											'date'			=> $p->getDate(),
											);
		
		$shift = $this->getDoctrine()->getRepository('AcmeUserBundle:Shift')->findOneBy(array(	'restaurant'	=> (int)$rid,
																								'date'			=> $date));
		if ($shift)
			$agreed = (int)$shift->getAgreed();
		else
			$agreed = 0;

		$edit_mode = 0;
		//план на завтра и дальше для утвержденного и неутвержденного - для менеджеров или директора
		if (	(
					$this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR') || $this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN')
				) && $date > date('Y-m-d')
			)
			$edit_mode = 1;


		if (	(	$agreed == 1 || 
					$date < date('Y-m-d') || 
					( $date == date('Y-m-d') && date('H')>14 )
				) && !$this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR')
			)
			$edit_mode = 0;

		// факт только для менеджеров и директоров сегодня до 14
		if (	(
					$this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') || $this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR')
				) && $date == date('Y-m-d') && date('H')<14
			)
			$edit_mode = 2; //редактируем только фактические часы

		//для управляющего все всегда можно редактировать
		if ($this->get('security.context')->isGranted('ROLE_ADMIN'))
			$edit_mode = 1;


		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
				
		$result = array('code' => 200, 'data' => $entities_array, 'agreed' => (int)$agreed, 'edit_mode' => $edit_mode);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	
    /**
     * @Route(	"api/company/{cid}/restaurant/{rid}/shift/{date}.{_format}", 
     * 			name="API_WorkingHours_create", 
     * 			requirements={	"_method" = "POST",
	 *							"_format" = "json|xml",
	 *							"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
     *			defaults={	"_format" = "json",
	 *						"date" = 0 })
     * @Template()
     * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN")
     */
    public function API_createAction($cid, $rid, $date, Request $request)
    {
		$restaurant = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->findOneByIdJoinedToCompany($rid, $cid);

		if (!$restaurant)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
		
		$company = $restaurant->getCompany();
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
		
		foreach ($restaurants AS $r)
			if ($r->getId() == $rid)
				$available_restaurant = $r;

		if (!isset($available_restaurant))
			return new Response('Нет доступа к ресторану', 403, array('Content-Type' => 'application/json'));

		if ($date == '0')
			$date = date('Y-m-d');
			

		if ($date <= date('Y-m-d') && !$this->get('security.context')->isGranted('ROLE_ADMIN') )
			return new Response('Запрещено редактировать старые смены', 403, array('Content-Type' => 'application/json'));
		
		$shift = $this->getDoctrine()->getRepository('AcmeUserBundle:Shift')->findOneBy(array(	'restaurant'	=> (int)$rid,
																								'date'			=> $date));
		if ($shift)
			$agreed = (int)$shift->getAgreed();
		else
			$agreed = 0;

		if ($agreed == 1 && !$this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR') )
			return new Response('Запрещено редактировать утвержденные смены', 403, array('Content-Type' => 'application/json'));

		$model = (array)json_decode($request->getContent());
		
		if ( count($model) > 0 && isset($model['user']) )
		{
			$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->find((int)$model['user']);
									
			if (!$user)
				return new Response('No user found for id '.(int)$model['user'], 404, array('Content-Type' => 'application/json'));
				
			$new_row = new WorkingHours();
			$new_row->setCompany($company);
			$new_row->setRestaurant($restaurant);
			$new_row->setUser($user);
			$new_row->setDate($date);
			$new_row->setPlanhours((int)$model['planhours']);
			$new_row->setFacthours((int)$model['facthours']);
			$new_row->setDescription($model['description']);
			
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
														'date' 			=> $new_row->getDate(), 
														'planhours'		=> $new_row->getPlanhours(),
														'facthours'		=> $new_row->getFacthours(),
														'description'	=> $new_row->getDescription() ));
				
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
	 *								"_format" = "json|xml",
	 *								"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={	"date" = 0,
	 *						"_format" = "json"})
	 * @Secure(roles="ROLE_ORDER_MANAGER, ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	public function API_deleteAction($cid, $rid, $date, $sid)
	{
		$restaurant = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->findOneByIdJoinedToCompany($rid, $cid);

		if (!$restaurant)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
		
		foreach ($restaurants AS $r)
			if ($r->getId() == $rid)
				$available_restaurant = $r;

		if (!isset($available_restaurant))
			return new Response('Нет доступа к ресторану', 403, array('Content-Type' => 'application/json'));

		if ($date == '0' || $date == 0) 
			$date = date('Y-m-d');
			
		$row = $this->getDoctrine()->getRepository('AcmeUserBundle:WorkingHours')->find($sid);
		
		if (!$row)
		{
			$result = array('code' => 200, 'data' => $sid, 'message' => 'Смена не найдена');
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
		}
		
		// если не управляющий можно редактировать только завтрашние смены
		if ($row->getDate() <= date('Y-m-d') &&	!$this->get('security.context')->isGranted('ROLE_ADMIN') )
			return new Response('Запрещено редактировать старые смены', 403, array('Content-Type' => 'application/json'));
		else
		{
			$shift = $this->getDoctrine()->getRepository('AcmeUserBundle:Shift')->findOneBy(array(	'restaurant'	=> (int)$rid,
																									'date'			=> $date));
			if ($shift)
				$agreed = (int)$shift->getAgreed();
			else
				$agreed = 0;

			if ($agreed == 1 && !$this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR') )
				return new Response('Запрещено редактировать утвержденные смены', 403, array('Content-Type' => 'application/json'));

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
	 *						"_format" = "json"	})
	 * @Secure(roles="ROLE_RESTAURANT_ADMIN, ROLE_COMPANY_ADMIN")
	 */
	public function API_updateAction($cid, $rid, $date, $sid, Request $request)
	{
		$restaurant = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->findOneByIdJoinedToCompany($rid, $cid);

		if (!$restaurant)
			return new Response('No restaurant found for id '.$rid.' in company #'.$cid, 404, array('Content-Type' => 'application/json'));
		
		$company = $restaurant->getCompany();
		
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
		
		foreach ($restaurants AS $r)
			if ($r->getId() == $rid)
				$available_restaurant = $r;

		if (!isset($available_restaurant))
			return new Response('Нет доступа к ресторану', 403, array('Content-Type' => 'application/json'));

		if ($date == '0' || $date == 0)
			$date = date('Y-m-d');
		
		$model = (array)json_decode($request->getContent());
			
		$row = $this->getDoctrine()->getRepository('AcmeUserBundle:WorkingHours')->find($sid);
		
		if (!$row)
			return new Response('Не найден элемент #'.$sid, 404, array('Content-Type' => 'application/json'));

		if (	(
					 $this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') ||
					 $this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR')
				) && $row->getDate() < date('Y-m-d') 
			)
		{
			// менеджер и деректор ресторана неможет редактировать вчерашнее
			return new Response('Запрещено редактировать старые смены', 403, array('Content-Type' => 'application/json'));
		}
		else
		{
			// редактируем только факт, доступно для менеджера и директора сегодня до 14
			if (	!$this->get('security.context')->isGranted('ROLE_ADMIN') && 
					(
						$this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') || 
						$this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR')
					) && 
					date('Y-m-d') == $date && 
					date('H') < 22
				)
			{
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
															'user'			=> $row->getUser()->getId(),
															'description'	=> $row->getDescription(),
															'date' 			=> $row->getDate(), 
															'planhours'		=> $row->getPlanhours(),
															'facthours'		=> $row->getFacthours() ));
					
					return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
				}
			}
			
			
			//сегодня после 14 нельзя редактировать менеджеру и директору
			if (	!$this->get('security.context')->isGranted('ROLE_ADMIN') && 
					(
						$this->get('security.context')->isGranted('ROLE_RESTAURANT_ADMIN') || 
						$this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR')
					) && date('Y-m-d') == $date && date('H') > 13 )
				return new Response('Запрещено редактировать старые смены', 403, array('Content-Type' => 'application/json'));
			
				
			$shift = $this->getDoctrine()->getRepository('AcmeUserBundle:Shift')->findOneBy(array(	'restaurant'	=> (int)$rid,
																									'date'			=> $date));
			if ($shift)
				$agreed = (int)$shift->getAgreed();
			else
				$agreed = 0;
			// утвержденные смены можно редактировать только с правами Директора ресторана
			if ($agreed == 1 && !$this->get('security.context')->isGranted('ROLE_RESTAURANT_DIRECTOR') )
				return new Response('Запрещено редактировать утвержденные смены', 403, array('Content-Type' => 'application/json'));

			if	(	count($model) > 0 && 
					isset($model['user']) && 
					is_numeric($model['id']) && $sid == $model['id'] && 
					isset($model['planhours']) &&
					isset($model['facthours'])	)
			{
				$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->find((int)$model['user']);
										
				if (!$user)
					return new Response('No user found for id '.(int)$model['user'], 404, array('Content-Type' => 'application/json'));
					
				$row->setUser($user);
				if (isset($model['description']))
					$row->setDescription($model['description']);

				if (isset($model['planhours']))
					$row->setPlanhours((int)$model['planhours']);

				if (isset($model['facthours']))
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
															'description'	=> $row->getDescription(),
															'date' 			=> $row->getDate(), 
															'planhours'		=> $row->getPlanhours(),
															'facthours'		=> $row->getFacthours() ));
					
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
	 *			defaults={	"_format" = "json"	})
	 * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_DIRECTOR")
	 */
    public function API_agreedAction($cid, $rid, $date, Request $request)
    {
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findOneCompanyOneRestaurant($cid, $rid);
		
		if (!$company)
			return new Response('Не найден ресторан #'.$rid.' в компании #'.$cid, 404, array('Content-Type' => 'application/json'));
	
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
		
		foreach ($restaurants AS $r)
			if ($r->getId() == $rid)
				$available_restaurant = $r;

		if (!isset($available_restaurant))
			return new Response('Нет доступа к ресторану', 403, array('Content-Type' => 'application/json'));

		$shift = $this->getDoctrine()->getRepository('AcmeUserBundle:Shift')->findOneBy(array(	'restaurant'	=> (int)$rid,
																								'date'			=> $date));
		if ($shift)
		{
			$shift->setAgreed(1);
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($shift);
			$em->flush();
		}
		else
		{
			$restaurant = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->find($rid);
			if (!$company)
				return new Response('Ресторан не найден #'.$rid, 404, array('Content-Type' => 'application/json'));
			
			$shift = new Shift();
			$shift->setAgreed(1);
			$shift->setDate($date);
			$shift->setRestaurant($restaurant);

			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($shift);
			$em->flush();
		}
		
		$result = array(	'code' => 200,
							'data' => array('date' => $shift->getDate(), 'restaurant' => $shift->getRestaurant()->getId(), 'agreed' => $shift->getAgreed() ));
		
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
    
	/**
	 * @Route(	"api/company/{cid}/restaurant/{rid}/shift/{date}/disagreed.{_format}", 
	 * 			name="API_WorkingHours_disagree", 
	 * 			requirements={	"_method" = "PUT",
	 *							"_format" = "json|xml",
	 *							"date" = "^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$"},
	 *			defaults={	"date" = 0,
	 *						"_format" = "json"	})
	 * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_DIRECTOR")
	 */
    public function API_disagreedAction($cid, $rid, $date, Request $request)
    {
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findOneCompanyOneRestaurant($cid, $rid);
		
		if (!$company)
			return new Response('Не найден ресторан #'.$rid.' в компании #'.$cid, 404, array('Content-Type' => 'application/json'));
	
		// check permission
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		$restaurants = $this->get("my.user.service")->getAvailableRestaurantsAction($cid);
		
		foreach ($restaurants AS $r)
			if ($r->getId() == $rid)
				$available_restaurant = $r;

		if (!isset($available_restaurant))
			return new Response('Нет доступа к ресторану', 403, array('Content-Type' => 'application/json'));

		$shift = $this->getDoctrine()->getRepository('AcmeUserBundle:Shift')->findOneBy(array(	'restaurant'	=> (int)$rid,
																								'date'			=> $date));
		if ($shift)
		{
			$shift->setAgreed(0);
			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($shift);
			$em->flush();
		}
		else
		{
			$restaurant = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->find($rid);
			if (!$company)
				return new Response('Ресторан не найден #'.$rid, 404, array('Content-Type' => 'application/json'));
			
			$shift = new Shift();
			$shift->setAgreed(0);
			$shift->setDate($date);
			$shift->setRestaurant($restaurant);

			$em = $this->getDoctrine()->getEntityManager();
			$em->persist($shift);
			$em->flush();
		}
		
		$result = array(	'code' => 200,
							'data' => array('date' => $shift->getDate(), 'restaurant' => $shift->getRestaurant()->getId(), 'agreed' => $shift->getAgreed() ));
		
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}

    /**
     * My Plan.
     *
     * @Route("calendar/{week}", name="my_calendar", defaults={	"week" = 0})
     * @Template()
     * @Secure(roles="ROLE_USER")
     */
    public function calendarAction($week)
    {
		$user = $this->get('security.context')->getToken()->getUser();
		
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();
		$securityIdentity = UserSecurityIdentity::fromAccount($user);
		$aclProvider = $this->get('security.acl.provider');
		$company = 0;
		foreach ($companies as $c) {
			try {

				$acl = $aclProvider->findAcl(ObjectIdentity::fromDomainObject($c), array($securityIdentity));
				
				foreach($acl->getObjectAces() as $a=>$ace)
				{
					if($ace->getSecurityIdentity()->getUsername() == $user->getUsername())
					{
						$company = $c->getId();
						break;
					}
				}

		    } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
		        
		    }
		}

		if ($company == 0)
			return $this->render('AcmeUserBundle::create_company.html.twig', array());
		else
		{

			if ($week == '0' || $week == 0)
				$week = date('W');
			
			$str = $week-date('W').' week';
			
			if (($timestamp = strtotime($str)) === -1)
				throw $this->createNotFoundException('Строка ($str) недопустима');
			
			$t = strtotime($str);
			
			$week_nav = array();
			for ($j=-3; $j<4; $j++)
				$week_nav[$week+$j] = date('d M',strtotime($week+$j-date('W').' week last Monday')).'-'.date('d M',strtotime($week+$j-date('W').' week next Sunday'));
			
			$user = $this->get('security.context')->getToken()->getUser();
			
			$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->loadUserByUsername($user->getUsername());
			
			if (!$user)
				throw $this->createNotFoundException('Пользователь не найден');
			
			$week = array();
			$used_restaraunts = array();

			for ($i=1; $i<8; $i++)
			{
				$date = date('Y-m-d',$t+( $i - date('w'))*24*3600);
				
				$entities = $this->getDoctrine()->getRepository('AcmeUserBundle:WorkingHours')->findBy(array(	'user'	=> $user->getId(),
																												'date'	=> $date	));
				$entities_array = array();
				if ($entities)
				{
					foreach ($entities AS $p)
					{
						$entities_array[$p->getRestaurant()->getId()] = array( 	'company'		=> $p->getCompany()->getName(),
																				'company_id'	=> $p->getCompany()->getId(),
																				'restaurant'	=> $p->getRestaurant()->getName(),
																				'restaurant_id'	=> $p->getRestaurant()->getId(),
																				'planhours'		=> $p->getPlanhours(),
																				'facthours'		=> $p->getFacthours(),
																				'description'	=> $p->getDescription(),
																				'date'			=> $p->getDate() 
																				);

						$used_restaraunts[$p->getRestaurant()->getId()] = $p->getRestaurant()->getName();
					}
				}
				else
				{
					$entities_array = null;
				}

				$week[$date] = $entities_array;
			
			}
			//$used_restaraunts = array_unique($used_restaraunts); var_dump($used_restaraunts); die;

			return array(	'week'			=> $week,
							'company'		=> $company,
							'date'			=> date('Y-m-d'),
							'week_nav'		=> $week_nav,
							'restaraunts'	=> $used_restaraunts,
							'curent_week' 	=> date('W', $t)	);
		}
	}
	
}
