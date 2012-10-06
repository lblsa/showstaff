<?php

namespace Acme\UserBundle\Controller;

use Acme\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class UserController extends Controller
{
    /**
     * @Route("/login", name="login")
     * @Template()
     */
    public function loginAction()
    {
        $request = $this->getRequest();
        $session = $request->getSession();


        // получить ошибки логина, если таковые имеются
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
        }

		return array(
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
            'target_path'	=> $session->get('_security.target_path')
        );
    }
	
    /**
     * @Route("/login_check", name="login_check")
     */
    public function logincheckAction()
    {
    }
	
    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
	}
	
    /**
     * @Route("/user", name="user",	requirements={"_method" = "GET"})
	 * )
     * @Template()
	 * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function listAction()
    {
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();
		$companies_array = array();
		if ($companies)
		{
			foreach ($companies AS $c)
				$companies_array[] = array(	'id'	=> $c->getId(),
											'name'	=> $c->getName());
		}
		
		
    	$users = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->findAll();
		$users_array = array();
		if ($users)
		{
			foreach ($users AS $p)
			{
				$role_super_admin = false;
				foreach ($p->getRoles() AS $r) 
					if ($r->getRole() == 'ROLE_COMPANY_ADMIN')
						$role_super_admin = true;
				
				if ($role_super_admin)
				{
					$users_array[] = array( 	'id'		=> $p->getId(),
												'username'	=> $p->getUsername(), 
												'email'		=> $p->getEmail(), 
												'password'	=> $p->getPassword(), 
												'company'	=> ($p->getCompany())?$p->getCompany()->getId():0,
												'fullname'	=> $p->getFullname(),
												'salt'		=> $p->getSalt(),
												'roles'		=> $p->getRoles(),	);
				}
			}
		}

		return array(	'users' => $users, 
						'users_json' => json_encode($users_array),
						'companies_json' => json_encode($companies_array) );
	}
	
	/**
	 * @Route(	"user/{uid}", 
	 * 			name="user_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
	 */
	 public function ajaxupdateAction($uid, Request $request)
	 {
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $uid == $model['id'])
		{
			$user = $this->getDoctrine()
							->getRepository('AcmeUserBundle:User')
							->find($model['id']);
			
			if (!$user)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No user found for id '.$uid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$validator = $this->get('validator');

			$user->setFullname($model['fullname']);
			
			if (isset($model['password']) && strlen($model['password'])>3)
			{
				// шифруем и устанавливаем пароль для пользователя,
				// эти настройки должны совпадать с конфигурационным файлом (security.yml - security: encoders:)
				$user->setSalt(md5(time()));
				$encoder = new MessageDigestPasswordEncoder('sha1', true, 10);
				$password = $encoder->encodePassword($model['password'], $user->getSalt());
				$user->setPassword($password);
			}	
			
			$user->setUsername($model['username']);
			$user->setEmail($model['email']);
			
			
			if ((int)$model['company'] > 0)
			{
				$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find((int)$model['company']);
							
				if (!$company) 
				{
					$result = array('has_error' => 1, 'result' => 'No company found for id '.(int)$model['company']);
					$response = new Response(json_encode($result), 200, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				
				$user->setCompany($company);
			}
			
			$errors = $validator->validate($user);
			
			if (count($errors) > 0)
			{
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
				
				$code = 400;
				$result = array('code'=>$code, 'message'=>$errorMessage);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
				
			}
			else
			{
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($user);
				$em->flush();
				
				$code = 200;
				
				$result = array('code'=> $code, 'data' => array(	'fullname' => $user->getFullname(),
																	'username' => $user->getUsername(), 
																	'email' => $user->getEmail(),
																	'company' => $user->getCompany()->getId(),
																));
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			
			}
		}
			
		$code = 400;
		$result = array('code'=> $code, 'message' => 'Invalid request');
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
		 
	 }
	 

	/**
	 * @Route(	"user", 
	 * 			name="user_ajax_create", 
	 * 			requirements={"_method" = "POST"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
	 */
	public function ajaxcreateAction(Request $request)
	{
		$model = (array)json_decode($request->getContent());
		
		if ( count($model) > 0 && isset($model['fullname']) && isset($model['username']) && isset($model['password']) && $model['password'] != '')
		{
			$validator = $this->get('validator');
			$user = new User();
			$user->setFullname($model['fullname']);
			$user->setUsername($model['username']);
			$user->setEmail($model['email']);
			
			// шифруем и устанавливаем пароль для пользователя,
			// эти настройки должны совпадать с конфигурационным файлом (security.yml - security: encoders:)
			$user->setSalt(md5(time()));
			$encoder = new MessageDigestPasswordEncoder('sha1', true, 10);
			$password = $encoder->encodePassword($model['password'], $user->getSalt());
			$user->setPassword($password);
			
			
			if ((int)$model['company'] > 0)
			{
				$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find((int)$model['company']);
							
				if (!$company) 
				{
					$code = 404;
					$result = array('code' => $code, 'message' => 'No company found for id '.(int)$model['company']);
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
			}
			
			$user->setCompany($company);
			
			$group = $this->getDoctrine()
							->getRepository('AcmeUserBundle:Group')
							->findOneByRole('ROLE_COMPANY_ADMIN');
			if (!$group)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No role "ROLE_COMPANY_ADMIN" found');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$user->addGroup($group);
			
			$errors = $validator->validate($user);
			
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
				$em->persist($user);
				$em->flush();
				
				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $user->getId(),
																		'fullname' => $user->getFullname(), 
																		'username' => $user->getUsername(), 
																		'email' => $user->getEmail(),
																		'company' => $user->getCompany()->getId(),
																	));
				
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
	 * @Route(	"/user/{uid}", 
	 * 			name="user_ajax_delete", 
	 * 			requirements={"_method" = "DELETE"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
	 */
	public function ajaxdeleteAction($uid, Request $request)
	{
		$user = $this->getDoctrine()
					->getRepository('AcmeUserBundle:User')
					->find($uid);
					
		if (!$user)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No user found for id '.$uid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($user);
		$em->flush();
		
		$code = 200;
		$result = array('code' => $code, 'data' => $uid);
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	}
	
	
	/**
	 * @Route(	"/", name="start_page" )
	 * @Template()
	 * @Secure(roles="ROLE_RESTAURANT_ADMIN, ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN, ROLE_SUPER_ADMIN")
	 */
	public function indexAction(Request $request)
	{
        $request = $this->getRequest();
        $session = $request->getSession();
		
		$user = $this->get('security.context')->getToken()->getUser();
		
		return array();
	}
	
    /**
     * @Route("/company/{cid}/user_to_restaurant", name="user_to_restaurant",	requirements={"_method" = "GET"})
     * @Template()
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
     */
    public function userToRestaurantAction($cid, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) //Если не супер админ, то проверим из какой компании наш ROLE_COMPANY_ADMIN
		{
			if ($user->getCompany()->getId() != $cid)
			{
				if ($request->isXmlHttpRequest()) 
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden');
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				} else {
					throw new AccessDeniedHttpException('Forbidden');
				}
			}
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($cid);
		
		if (!$company)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
		$available_roles = $this->getDoctrine()->getRepository('AcmeUserBundle:Group')->findBy(array('id' => array(3,5))); // available roles

		if ($available_roles)
		{
			foreach ($available_roles AS $r)
				$roles_array[] = array( 'id' => $r->getId(),
										'name' => $r->getName(),
										'role' => $r->getRole(), );
				
		}

		$restaurants = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->findByCompany($cid);
					
		$restaurants_array = array();
		if ($restaurants)
		{
			foreach ($restaurants AS $r)
			{
				$restaurants_array[] = array(	'id'	=> $r->getId(),
												'name'	=> $r->getName()	);
			}
		}
		
		$users = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->findByCompany($cid);
					
		$users_array = array();
		if ($users)
		{
			foreach ($users AS $p)
			{
				$available_role = true;
				foreach ($p->getRoles() AS $r)
				{
					$role = $r->getRole();
					//var_dump($role);
					if ($r->getRole() == 'ROLE_SUPER_ADMIN' || $r->getRole() == 'ROLE_COMPANY_ADMIN')
						$available_role = false;
				}
				
				$my_restaurants = array();
				foreach ($p->getRestaurants() AS $r)
				{
					$my_restaurants[] = $r->getId();
				}
					
				if ($available_role)
				{
					$roles = array();
					foreach ($p->getRoles() AS $r)
						$roles[] = $r->getId();
					
					$users_array[] = array( 	'id'		=> $p->getId(),
												'username'	=> $p->getUsername(), 
												'email'		=> $p->getEmail(), 
												'password'	=> $p->getPassword(), 
												'company'	=> ($p->getCompany())?$p->getCompany()->getId():0,
												'fullname'	=> $p->getFullname(),
												'salt'		=> $p->getSalt(),
												'roles'		=> $roles,
												'restaurants'	=> $my_restaurants,
											);
				}
			}
		}

		return array(	'users' => $users, 
						'users_json' => json_encode($users_array),
						'company' => $company,
						'roles_json' => json_encode($roles_array),
						'restaurants_json' => json_encode($restaurants_array));
	}
	
	
    /**
     * @Route("/company/{cid}/user", name="user_management",	requirements={"_method" = "GET"})
     * @Template()
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
     */
    public function listByCompanyAction($cid, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) //Если не супер админ, то проверим из какой компании наш ROLE_COMPANY_ADMIN
		{
			if ($user->getCompany()->getId() != $cid)
			{
				if ($request->isXmlHttpRequest()) 
				{
					$code = 403;
					$result = array('code' => $code, 'message' => 'Forbidden');
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				} else {
					throw new AccessDeniedHttpException('Forbidden');
				}
			}
		}
		
		$company = $this->getDoctrine()
						->getRepository('SupplierBundle:Company')
						->find($cid);
		
		if (!$company)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
		$available_roles = $this->getDoctrine()->getRepository('AcmeUserBundle:Group')->findBy(array('id' => array(3,4,5))); // available roles

		if ($available_roles)
		{
			foreach ($available_roles AS $r)
				$roles_array[] = array( 'id' => $r->getId(),
										'name' => $r->getName(),
										'role' => $r->getRole(), );
				
		}

		
		$users = $this->getDoctrine()
					->getRepository('AcmeUserBundle:User')
					->findByCompany($cid);

		$users_array = array();
		if ($users)
		{
			foreach ($users AS $p)
			{
				$available_role = true;
				foreach ($p->getRoles() AS $r)
				{
					$role = $r->getRole();
					//var_dump($role);
					if ($r->getRole() == 'ROLE_SUPER_ADMIN' || $r->getRole() == 'ROLE_COMPANY_ADMIN')
						$available_role = false;
				}
				if ($available_role)
				{
					$roles = array();
					foreach ($p->getRoles() AS $r)
						$roles[] = $r->getId();
					
					$users_array[] = array( 	'id'		=> $p->getId(),
												'username'	=> $p->getUsername(), 
												'email'		=> $p->getEmail(), 
												'password'	=> $p->getPassword(), 
												'company'	=> ($p->getCompany())?$p->getCompany()->getId():0,
												'fullname'	=> $p->getFullname(),
												'salt'		=> $p->getSalt(),
												'roles'		=> $roles,
											);
				}
			}
		}

		return array(	'users' => $users, 
						'users_json' => json_encode($users_array),
						'company' => $company,
						'roles_json' => json_encode($roles_array));
	}
	
	
	/**
	 * @Route(	"/company/{cid}/user/{uid}", 
	 * 			name="user_ajax_delete_manager", 
	 * 			requirements={"_method" = "DELETE"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function ajaxdeleteManagerAction($cid, $uid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) //Если не супер админ, то проверим из какой компании наш ROLE_COMPANY_ADMIN
		{
			if ($user->getCompany()->getId() != $cid)
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
		}

		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find((int)$cid);
					
		if (!$company) 
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company found for id '.(int)$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
		$del_user = $this->getDoctrine()
					->getRepository('AcmeUserBundle:User')
					->find($uid);
		if (!$del_user)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No user found for id '.$uid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($del_user);
		$em->flush();
		
		$code = 200;
		$result = array('code' => $code, 'data' => $uid);
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	}

	/**
	 * @Route(	"/company/{cid}/user_to_restaurant/{uid}", 
	 * 			name="to_restaurant_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function ajaxupdateToRestaurantAction($cid, $uid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) //Если не супер админ, то проверим из какой компании наш ROLE_COMPANY_ADMIN
		{
			if ($user->getCompany()->getId() != $cid)
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
		}

		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find((int)$cid);
					
		if (!$company) 
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company found for id '.(int)$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $uid == $model['id'])
		{
			$user = $this->getDoctrine()
							->getRepository('AcmeUserBundle:User')
							->find($model['id']);
			
			if (!$user)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No user found for id '.$uid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$validator = $this->get('validator');
			
			if (isset($model['restaurants']) && is_array($model['restaurants']) )
			{	
				$restaurants = array();
				$user->cleanRestaurant();
				foreach ($model['restaurants'] AS $r)
				{
					$restaurant = $this->getDoctrine()
									->getRepository('SupplierBundle:Restaurant')
									->find($r);
					if ($restaurant)
					{
						$user->addRestaurant($restaurant);
						$restaurants[] = $r;					
					}
				}
				$errors = $validator->validate($user);
				
				if (count($errors) > 0)
				{
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();
					
					$code = 400;
					$result = array('code'=>$code, 'message'=>$errorMessage);
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
					
				}
				else
				{
					
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($user);
					$em->flush();
					
					$code = 200;
					
					$result = array('code'=> $code, 'data' => array('restaurants' => $restaurants	));
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				
				}
				
			}
			
		}
		
	}
	
	
	
	
	/**
	 * @Route(	"/company/{cid}/user/{uid}", 
	 * 			name="manager_ajax_update", 
	 * 			requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function ajaxupdateManagerAction($cid, $uid, Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) //Если не супер админ, то проверим из какой компании наш ROLE_COMPANY_ADMIN
		{
			if ($user->getCompany()->getId() != $cid)
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
		}

		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find((int)$cid);
					
		if (!$company) 
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No company found for id '.(int)$cid);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $uid == $model['id'])
		{
			$user = $this->getDoctrine()
							->getRepository('AcmeUserBundle:User')
							->find($model['id']);
			
			if (!$user)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No user found for id '.$uid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$validator = $this->get('validator');

			$user->setFullname($model['fullname']);
			$user->setPassword('');
			$user->setSalt('');
			$user->setUsername($model['username']);
			$user->setEmail($model['email']);
			
			if (isset($model['roles']) && is_array($model['roles']) )
			{
				$available_roles = $this->getDoctrine()->getRepository('AcmeUserBundle:Group')->findBy(array('id' => array(3,4,5))); // available roles
				$roles_array = array();
				if ($available_roles)
					foreach ($available_roles AS $r)
						$roles_array[] = $r->getId();
				
				$roles = array();
				$user->cleanGroup();
				foreach ($model['roles'] AS $r) {
					if (in_array($r,$roles_array))
					{
						$group = $this->getDoctrine()
										->getRepository('AcmeUserBundle:Group')
										->find($r);
						if ($group)
						{
							$user->addGroup($group);
							$roles[] = $r;					
						}
					}
				}
				$errors = $validator->validate($user);
				
				if (count($errors) > 0)
				{
					
					foreach($errors AS $error)
						$errorMessage[] = $error->getMessage();
					
					$code = 400;
					$result = array('code'=>$code, 'message'=>$errorMessage);
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
					
				}
				else
				{
					
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($user);
					$em->flush();
					
					$code = 200;
					
					$result = array('code'=> $code, 'data' => array(	'fullname'	=> $user->getFullname(),
																		'username'	=> $user->getUsername(), 
																		'email'		=> $user->getEmail(),
																		'company'	=> $user->getCompany()->getId(),
																		'roles'		=> $roles,
																	));
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				
				}
				
			}
			
		}
	}
	
	
    /**
     * @Route("/company/{cid}/user", name="manager_ajax_create", requirements={"_method" = "POST"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
     */
    public function ajaxcreateManagerAction($cid, Request $request)
    {		
		$user = $this->get('security.context')->getToken()->getUser();
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN')) //Если не супер админ, то проверим из какой компании наш ROLE_COMPANY_ADMIN
		{
			if ($user->getCompany()->getId() != $cid)
			{
				$code = 403;
				$result = array('code' => $code, 'message' => 'Forbidden');
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
		}
		
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['fullname']) && isset($model['username']))
		{
			$validator = $this->get('validator');
			$user = new User();
			$user->setFullname($model['fullname']);
			$user->setPassword('');
			$user->setSalt('');
			$user->setUsername($model['username']);
			$user->setEmail($model['email']);
			

			$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find((int)$cid);
						
			if (!$company) 
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No company found for id '.(int)$cid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}

			
			$user->setCompany($company);
			
			$available_roles = $this->getDoctrine()->getRepository('AcmeUserBundle:Group')->findBy(array('id' => array(3,4,5)));
			$roles_array = array();
			if ($available_roles)
				foreach ($available_roles AS $r)
					$roles_array[] = $r->getId();
			
			if (isset($model['roles']) && is_array($model['roles']) )
			{
				$roles = array();
				$user->cleanGroup();
				foreach ($model['roles'] AS $r) {
					if (in_array($r,$roles_array))
					{
						$group = $this->getDoctrine()
										->getRepository('AcmeUserBundle:Group')
										->find($r);
						if ($group)
						{
							$user->addGroup($group);
							$roles[] = $r;					
						}
					}
				}
			}
			
			$errors = $validator->validate($user);
			
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
				$em->persist($user);
				$em->flush();
				
				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $user->getId(),
																		'fullname' => $user->getFullname(), 
																		'username' => $user->getUsername(), 
																		'email' => $user->getEmail(),
																		'roles' => $model['roles'],
																	));
				
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
}
