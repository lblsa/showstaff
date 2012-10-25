<?php

namespace Acme\UserBundle\Controller;

use Acme\UserBundle\Entity\User;
use Acme\UserBundle\Entity\Permission;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
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
     * @Route("/role", name="roles", requirements={"_method" = "GET"})
     * @Template()
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
     */
    public function listRolesAction(Request $request)  // only COMPANY_ADMIN
    {
		$available_roles = $this->getDoctrine()
							->getRepository('AcmeUserBundle:Role')
							->findBy(array('role' => array(
															'ROLE_RESTAURANT_ADMIN',
															'ROLE_ORDER_MANAGER',
															'ROLE_ADMIN'))); // available roles for company admin

		if ($available_roles)
		{
			foreach ($available_roles AS $r)
				$roles_array[] = array( 'id' => $r->getId(),
										'name' => $r->getName(),
										'role' => $r->getRole(), );
				
		}
		
		$code = 200;
		$result = array('code' => $code, 'data' => $roles_array);
		$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
		$response->sendContent();
		die();
	}
	
    /**
     * @Route("/user", name="user",	requirements={"_method" = "GET"})
     * @Template()
	 * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function listAction(Request $request)  // only COMPANY_ADMIN
    {
		$user = $this->get('security.context')->getToken()->getUser();
		
		$role_id = 'ROLE_COMPANY_ADMIN';
		
		$role = $this->getDoctrine()->getRepository('AcmeUserBundle:Role')->findOneBy(array('role'=>$role_id));
		
		if (!$role)
		{
			$code = 404;
			$result = array('code' => $code, 'message' => 'No role found for id '.$role_id);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();
		}
		
		$users = $role->getUsers();
		
		$users_array = array();
		
		if ($users)
		{
			foreach ($users AS $p)
			{
				$roles = array();
				foreach ($p->getRoles() AS $r)
					$roles[] = array(	'id'	=>	$r->getId(),
										'name'	=>	$r->getName(),
										'role'	=>	$r->getRole()	);
				
				$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($p->getId());
				
				$company = 0;
				if ($permission != null && $permission->getCompany() != null)
					$company = $permission->getCompany()->getId();
					
				$users_array[] = array( 	'id'		=> $p->getId(),
											'username'	=> $p->getUsername(), 
											'email'		=> $p->getEmail(),
											'fullname'	=> $p->getFullname(),
											'roles'		=> $roles,
											'company'	=> $company );
			}
		}
		
		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $users_array);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
			header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");// HTTP/1.0
			$response->sendContent();
			die();
		}

		return array( 'users_json' => json_encode($users_array) );
	}

	/**
	 * @Route(	"/company/{cid}/user/{uid}", name="user_ajax_update_manag", requirements={"_method" = "PUT"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	 public function ajaxupdateManagerAction($cid, $uid, Request $request)
	 {
		$curent_user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($curent_user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid || $this->get('security.context')->isGranted('ROLE_ADMIN')) // проверим из какой компании
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
		}
		
		$model = (array)json_decode($request->getContent());
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $uid == $model['id'])
		{
			$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->find($model['id']);
			if (!$user)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No user found for id '.$uid);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
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
			
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());
			if (!$permission) // Если еще не существаволо то создадим
			{
				$permission = new Permission();
				$permission->setUser($user);
				$permission->setCompany($company);
			} else {
				$permission->setCompany($company);	
			}
			
			//* User
			$validator = $this->get('validator');

			if (isset($model['fullname']) && strlen($model['fullname']))
				$user->setFullname($model['fullname']);
			
			if (isset($model['username']) && strlen($model['username']))
				$user->setUsername($model['username']);
				
			if (isset($model['email']) && strlen($model['email']))
				$user->setEmail($model['email']);
			
			if (isset($model['password']) && strlen($model['password']))
				$user->setPassword($model['password']);

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
				if (isset($model['password']) && strlen($model['password'])>5)
				{
					// шифруем и устанавливаем пароль для пользователя,
					// эти настройки должны совпадать с конфигурационным файлом (security.yml - security: encoders:)
					$user->setSalt(md5(time()));
					$encoder = new MessageDigestPasswordEncoder('sha1', true, 10);
					$password = $encoder->encodePassword($model['password'], $user->getSalt());
					$user->setPassword($password);
				}
				
				$roles = array();
				if (isset($model['roles']) && is_array($model['roles']) && count($model['roles'])>0)
				{
					$user->cleanRoles();
					$available_roles = $this->getDoctrine()
											->getRepository('AcmeUserBundle:Role')
											->findBy(array('role' => array(	'ROLE_RESTAURANT_ADMIN',
																			'ROLE_ORDER_MANAGER',
																			'ROLE_ADMIN')));
					
					foreach ($available_roles AS $r)
						if (in_array($r->getId(), $model['roles']))
						{
							$user->addRole($r);
							$roles[] = $r->getId();
						}
				}
			
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($user);
				$em->flush();
				
				$restaurants = array();
				if (isset($model['restaurants']) && is_array($model['restaurants']))
				{
					$permission->cleanRestaurants();
					$available_restaurants = $this->getDoctrine()
										->getRepository('SupplierBundle:Restaurant')
										->findByCompany($permission->getCompany()->getId());
					
					foreach ($available_restaurants AS $r)
						if (in_array($r->getId(), $model['restaurants']))
						{
							$permission->addRestaurant($r);
							$restaurants[] = $r->getId();
						}
				}
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($permission);
				$em->flush();


				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $user->getId(),
																		'fullname' => $user->getFullname(), 
																		'username' => $user->getUsername(), 
																		'email' => $user->getEmail(),
																		'restaurants' => $restaurants,
																		'roles' => $roles,
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
	 * @Route(	"user/{uid}", name="user_ajax_update", requirements={"_method" = "PUT"})
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
			
			if (isset($model['company']) && (int)$model['company'] > 0)
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
				
				$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());
				if (!$permission) // Если еще не существаволо то создадим
				{
					$permission = new Permission();
					$permission->setUser($user);
					$permission->setCompany($company);
				} else {
					$permission->setCompany($company);	
				}
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($permission);
				$em->flush();
			} else {
				$model['company'] = 0;
			}
			
			$validator = $this->get('validator');
			
			if (isset($model['fullname']) && strlen($model['fullname']) > 0)
				$user->setFullname($model['fullname']);
			
			if (isset($model['username']) && strlen($model['username']) > 0)
				$user->setUsername($model['username']);
			
			if (isset($model['email']) && strlen($model['email']) > 0)
				$user->setEmail($model['email']);
			
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
				if (isset($model['password']) && strlen($model['password'])>5)
				{
					// шифруем и устанавливаем пароль для пользователя,
					// эти настройки должны совпадать с конфигурационным файлом (security.yml - security: encoders:)
					$user->setSalt(md5(time()));
					$encoder = new MessageDigestPasswordEncoder('sha1', true, 10);
					$password = $encoder->encodePassword($model['password'], $user->getSalt());
					$user->setPassword($password);
				}
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($user);
				$em->flush();
				
				$code = 200;
				
				$result = array('code'=> $code, 'data' => array(	'fullname' => $user->getFullname(),
																	'username' => $user->getUsername(), 
																	'email' => $user->getEmail(),
																	'company' => $model['company'],
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
	 * @Route(	"/company/{cid}/user", name="user_ajax_create_manag", requirements={"_method" = "POST"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function ajaxcreateManagerAction($cid, Request $request) // create company manager
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
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
				}
			}
		}
		
		$model = (array)json_decode($request->getContent());
		
		if ( count($model) > 0 && isset($model['fullname']) && isset($model['username']) && isset($model['password']) )
		{
			$validator = $this->get('validator');
			$new_user = new User();
			$new_user->setFullname($model['fullname']);
			$new_user->setUsername($model['username']);
			$new_user->setEmail($model['email']);
			$new_user->setPassword($model['password']);
			$errors = $validator->validate($new_user);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
					
				$code = 400;
				$result = array('code' => $code, 'message'=>$errorMessage);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
				
			} else {
				// шифруем и устанавливаем пароль для пользователя,
				// эти настройки должны совпадать с конфигурационным файлом (security.yml - security: encoders:)		
				$new_user->setSalt(md5(time()));
				$encoder = new MessageDigestPasswordEncoder('sha1', true, 10);
				$password = $encoder->encodePassword($model['password'], $new_user->getSalt());
				$new_user->setPassword($password);
				
				$roles = array();
				if (isset($model['roles']) && is_array($model['roles']) && count($model['roles'])>0)
				{
					$available_roles = $this->getDoctrine()
											->getRepository('AcmeUserBundle:Role')
											->findBy(array('role' => array(	'ROLE_RESTAURANT_ADMIN',
																			'ROLE_ORDER_MANAGER',
																			'ROLE_ADMIN')));
					
					foreach ($available_roles AS $r)
						if (in_array($r->getId(), $model['roles']))
						{
							$new_user->addRole($r);
							$roles[] = $r->getId();
						}
				}
			
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($new_user);
				$em->flush();
			
				$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
								
				if (!$company)
				{
					$code = 404;
					$result = array('code' => $code, 'message' => 'No company found for id '.$cid);
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				}
				
				$permission = new Permission();
				$permission->setUser($new_user);
				$permission->setCompany($company);
				
				$restaurants = array();
				if (isset($model['restaurants']) && is_array($model['restaurants']) && count($model['restaurants'])>0)
				{
					$available_restaurants = $this->getDoctrine()
										->getRepository('SupplierBundle:Restaurant')
										->findByCompany($permission->getCompany()->getId());
					
					foreach ($available_restaurants AS $r)
						if (in_array($r->getId(), $model['restaurants']))
						{
							$permission->addRestaurant($r);
							$restaurants[] = $r->getId();
						}
				}
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($permission);
				$em->flush();
				
				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $new_user->getId(),
																		'fullname' => $new_user->getFullname(), 
																		'username' => $new_user->getUsername(), 
																		'email' => $new_user->getEmail(),
																		'restaurants' => $restaurants,
																		'roles' => $roles,
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
	 * @Route(	"user", name="user_ajax_create", requirements={"_method" = "POST"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
	 */
	public function ajaxcreateAction(Request $request) // create company admin
	{
		$model = (array)json_decode($request->getContent());
		
		if ( count($model) > 0 && isset($model['fullname']) && isset($model['username']) && isset($model['password']) )
		{
			$role_id = 'ROLE_COMPANY_ADMIN';
			
			$role = $this->getDoctrine()->getRepository('AcmeUserBundle:Role')->findOneBy(array('role'=>$role_id));
			
			if (!$role)
			{
				$code = 404;
				$result = array('code' => $code, 'message' => 'No role found for id '.$role_id);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
			}
			
			$validator = $this->get('validator');
			$user = new User();
			$user->setFullname($model['fullname']);
			$user->setUsername($model['username']);
			$user->setEmail($model['email']);
			$user->addRole($role);
			$user->setPassword($model['password']);
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
				// шифруем и устанавливаем пароль для пользователя,
				// эти настройки должны совпадать с конфигурационным файлом (security.yml - security: encoders:)		
				$user->setSalt(md5(time()));
				$encoder = new MessageDigestPasswordEncoder('sha1', true, 10);
				$password = $encoder->encodePassword($model['password'], $user->getSalt());
				$user->setPassword($password);
			
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($user);
				$em->flush();
				
				if (isset($model['company']) && (int)$model['company'] > 0)
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
					$permission = new Permission();
					$permission->setUser($user);
					$permission->setCompany($company);
					
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($permission);
					$em->flush();
				} else {
					$model['company'] = 0;
				}
				
				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $user->getId(),
																		'fullname' => $user->getFullname(), 
																		'username' => $user->getUsername(), 
																		'email' => $user->getEmail(),
																		'company' => (int)$model['company'],
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
	 * @Route(	"/company/{cid}/user/{uid}", name="user_ajax_delete_manag", requirements={"_method" = "DELETE"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function ajaxdeleteManagerAction($cid, $uid, Request $request)
	{
		$curent_user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($curent_user->getId());

			if (!$permission || $permission->getCompany()->getId() != $cid || $this->get('security.context')->isGranted('ROLE_ADMIN')) // проверим из какой компании
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
		}
		
		$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->find($uid);
					
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
	 * @Secure(roles="ROLE_RESTAURANT_ADMIN, ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN, ROLE_SUPER_ADMIN, ROLE_ADMIN")
	 */
	public function indexAction(Request $request)
	{
        $request = $this->getRequest();
        $session = $request->getSession();
		
		$user = $this->get('security.context')->getToken()->getUser();
		
		$ROLE_ADMIN = 0;
		if ($this->get('security.context')->isGranted('ROLE_ADMIN'))
			$ROLE_ADMIN = 1;
		
		if (false === $this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$permission = $this->getDoctrine()->getRepository('AcmeUserBundle:Permission')->find($user->getId());
			
			if (!$permission)
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
			} else {
				$company = $permission->getCompany();
				return array('cid' => $company->getId(), 'ROLE_ADMIN'=>$ROLE_ADMIN);
			}
		}


			return array('ROLE_ADMIN'=>$ROLE_ADMIN);
	}
	
	
    /**
     * @Route("/company/{cid}/user", name="user_management", requirements={"_method" = "GET"})
     * @Template()
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
     */
    public function listByCompanyAction($cid, Request $request)
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
					$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
					$response->sendContent();
					die();
				} else {
					throw new AccessDeniedHttpException('Forbidden Company');
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
		
		$available_roles = $this->getDoctrine()->getRepository('AcmeUserBundle:Role')->findBy(array('role' => array('ROLE_RESTAURANT_ADMIN','ROLE_ORDER_MANAGER','ROLE_ADMIN'))); // available roles

		if ($available_roles)
		{
			foreach ($available_roles AS $r)
				$roles_array[] = array( 'id' => $r->getId(),
										'name' => $r->getName(),
										'role' => $r->getRole(), );
				
		}

		
		$permissions = $this->getDoctrine()
					->getRepository('AcmeUserBundle:Permission')
					->findByCompany($cid);

		$users_array = array();
		if ($permissions)
		{
			foreach ($permissions AS $p)
			{
				$available_role = true;
				foreach ($p->getUser()->getRoles() AS $r)
					if ($r->getRole() == 'ROLE_COMPANY_ADMIN')
						$available_role = false;
						
				if ($available_role)
				{
					$restaurants = array();
					foreach ($p->getRestaurants() AS $r)
						$restaurants[] = $r->getId();
					
					
					$roles = array();
					foreach ($p->getUser()->getRoles() AS $r)
						$roles[] = $r->getId();
					
					$users_array[] = array( 	'id'		=> $p->getUser()->getId(),
												'username'	=> $p->getUser()->getUsername(), 
												'email'		=> $p->getUser()->getEmail(),  
												'company'	=> $cid,
												'fullname'	=> $p->getUser()->getFullname(),
												'roles'		=> $roles,
												'restaurants'		=> $restaurants,
											);
				}
			}
		}

		if ($request->isXmlHttpRequest()) 
		{
			$code = 200;
			$result = array('code' => $code, 'data' => $users_array);
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
			header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");// HTTP/1.0
			$response->sendContent();
			die();
		}

		return array(	'users_json' => json_encode($users_array),
						'company' => $company );
	}
	
	

	
	
	/**
	 * @Route(	"/feedback", name="feedback", requirements={"_method" = "PUT"})
	 */
	public function feedbackAction(Request $request)
	{
		$user = $this->get('security.context')->getToken()->getUser();
		$data = (array)json_decode($request->getContent());

		if (count($data) > 0 && isset($data['feedback_message']) && $data['feedback_message'] != '')
		{
			$data['feedback_message'] = str_replace(array("#quot;", "#039;"), array("\"", "'"), $data['feedback_message']);
			$message = \Swift_Message::newInstance()
				->setSubject($data['feedback_message'])
				->setFrom('tester@showstaff.ru')
				->setTo(array('x+1226812676413@mail.asana.com', 'vladimir.stasevich@gmail.com'))
				->setBody(	$this->renderView(	'AcmeUserBundle:User:email_error_report.txt.twig',
												array(	'feedback_message' => $data['feedback_message'],
                                                        'username' => $user->getUsername(),
														'url' => 'http://'.$_SERVER['HTTP_HOST'].$data['url'] )));
			
			$this->get('mailer')->send($message);

			$code = 200;
			$result = array('code' => $code, 'message'=> 'Succes send');
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();	

		} else {
			
			$code = 400;
			$result = array('code' => $code, 'message'=> 'Invalid request');
			$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
			$response->sendContent();
			die();

		}	
	}

}
