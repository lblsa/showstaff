<?php

namespace Acme\UserBundle\Controller;

use Acme\UserBundle\Entity\User;
use Acme\UserBundle\Form\Type\UserType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation\Secure;

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
     * @Route(	"api/role.{_format}",
	 *			name="API_roles",
	 *			requirements={"_method" = "GET", "format" = "json|xml"},
	 *			defaults={"_format" = "json"})
     * @Template()
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
     */
    public function API_listRolesAction(Request $request)  // only COMPANY_ADMIN
    {
		$available_roles = $this->getDoctrine()
							->getRepository('AcmeUserBundle:Role')
							->findBy(array('role' => array(
															'ROLE_RESTAURANT_DIRECTOR',
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
		
		$result = array('code' => 200, 'data' => $roles_array);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}

    /**
     * @Route(	"api/user.{_format}",
	 *			name="API_user",
	 *			requirements={"_method" = "GET", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"} )
     * @Template()
	 * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function API_listAction(Request $request)  // only COMPANY_ADMIN
    {
		$user = $this->get('security.context')->getToken()->getUser();
		
		$role_id = 'ROLE_COMPANY_ADMIN';
		
		$role = $this->getDoctrine()->getRepository('AcmeUserBundle:Role')->findOneBy(array('role'=>$role_id));
		
		if (!$role)
			return new Response('No role found for id '.$role_id, 404, array('Content-Type' => 'application/json'));
		
		$users = $role->getUsers();
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();

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
				
							
				
				$securityIdentity = UserSecurityIdentity::fromAccount($user);
				$aclProvider = $this->get('security.acl.provider');
				$company = 0;
				foreach ($companies as $c) {
					try {

						$acl = $aclProvider->findAcl(ObjectIdentity::fromDomainObject($c), array($securityIdentity));
						
						foreach($acl->getObjectAces() as $a=>$ace)
						{
							if($ace->getSecurityIdentity()->getUsername() == $p->getUsername())
							{
								$company = $c->getId();
								break;
							}
						}

				    } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
				        
				    }
				}

				$users_array[] = array( 	'id'		=> $p->getId(),
											'username'	=> $p->getUsername(), 
											'email'		=> $p->getEmail(),
											'fullname'	=> $p->getFullname(),
											'roles'		=> $roles,
											'company'	=> $company );
			}
		}
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
			
		$result = array('code' => 200, 'data' => $users_array);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
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
			throw $this->createNotFoundException('Роль не найдена');
		
		$users = $role->getUsers();
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();
		
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
				
				$securityIdentity = UserSecurityIdentity::fromAccount($user);
				$aclProvider = $this->get('security.acl.provider');
				$company = 0;
				foreach ($companies as $c) {
					try {

						$acl = $aclProvider->findAcl(ObjectIdentity::fromDomainObject($c), array($securityIdentity));
						
						foreach($acl->getObjectAces() as $a=>$ace)
						{
							if($ace->getSecurityIdentity()->getUsername() == $p->getUsername())
							{
								$company = $c->getId();
								break;
							}
						}

				    } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
				        
				    }
				}
					
				$users_array[] = array( 	'id'		=> $p->getId(),
											'username'	=> $p->getUsername(), 
											'email'		=> $p->getEmail(),
											'fullname'	=> $p->getFullname(),
											'roles'		=> $roles,
											'company'	=> $company );
			}
		}
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0

		return array( 'users_json' => json_encode($users_array) );
	}

	/**
	 * @Route(	"api/company/{cid}/user/{uid}.{_format}",
	 *			name="API_user_update_manag",
	 *			requirements={"_method" = "PUT", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	 public function API_updateManagerAction($cid, $uid, Request $request)
	 {
		$curent_user = $this->get('security.context')->getToken()->getUser();
		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find((int)$cid);				
		if (!$company)
			return new Response('Компания не найдена', 404, array('Content-Type' => 'application/json'));

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
		$model = (array)json_decode($request->getContent());
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $uid == $model['id'])
		{
			$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->find($model['id']);
			if (!$user)
				return new Response('No user found for id '.$uid, 404, array('Content-Type' => 'application/json'));
			
			//--> Add permisson VIEW to curent company
			$securityIdentity = UserSecurityIdentity::fromAccount($user);
			$objectIdentity = ObjectIdentity::fromDomainObject($company);
			$aclProvider = $this->get('security.acl.provider');

			$builder = new MaskBuilder();
			$builder->add('view');
			$mask = $builder->get();  // create only view mask

			try {
				$acl = $aclProvider->findAcl($objectIdentity);
				
				$exist = 0;
				
				if (count($acl->getObjectAces()))
					foreach ($acl->getObjectAces() as $ace)
						if ($ace->getSecurityIdentity()->getUsername() == $user->getUsername())
							$exist = 1;

				if (!$exist)
				{
					$acl->insertObjectAce($securityIdentity, $mask); // add view access
					$aclProvider->updateAcl($acl);
				}

			} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
				
			}
			//<-- Add permisson VIEW to curent company

			//* User
			$validator = $this->get('validator');

			if (isset($model['fullname']))
				$user->setFullname($model['fullname']);
			
			if (isset($model['username']))
				$user->setUsername($model['username']);
				
			if (isset($model['email']))
				$user->setEmail($model['email']);
			
			if (isset($model['password']) && strlen($model['password']))
				$user->setPassword($model['password']);

			$errors = $validator->validate($user);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
				
				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
				
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
																			'ROLE_RESTAURANT_DIRECTOR',
																			'ROLE_ORDER_MANAGER',
																			'ROLE_ADMIN')));
					
					foreach ($available_roles AS $r)
						if (in_array($r->getId(), $model['roles']))
						{
							$user->addRole($r);
							$roles[] = $r->getId();
						}
				}
				else
				{
					$user->cleanRoles();
				}
			
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($user);
				$em->flush();
				
				$available_restaurants = array();
				if (isset($model['restaurants']) && is_array($model['restaurants']))
				{
					//--> Add permisson VIEW to restaurants
					$restaurants = $company->getRestaurants();
					
					if ($restaurants)
						foreach ($restaurants as $restaurant)
						{
							$objectIdentity = ObjectIdentity::fromDomainObject($restaurant);
							
							$builder = new MaskBuilder();
							$builder->add('view');
							$mask = $builder->get();  // create only view mask
							
							try {
								$acl = $aclProvider->findAcl($objectIdentity);

								if (count($acl->getObjectAces()) > 0)
								{
									$exist = 0;
									foreach($acl->getObjectAces() as $a=>$ace)
									{
										if($ace->getSecurityIdentity()->getUsername() == $user->getUsername()) // only curent user
										{
											if (!in_array($restaurant->getId(), $model['restaurants']))
												$acl->deleteObjectAce($a);
											else
												$available_restaurants[] = $restaurant->getId();

											$exist = 1;
										}
									}

									if (!$exist && in_array($restaurant->getId(), $model['restaurants']))
									{
										$available_restaurants[] = $restaurant->getId();
										$acl->insertObjectAce($securityIdentity, $mask);
									}

								}
								else
								{
									if (in_array($restaurant->getId(), $model['restaurants']))
									{
										$available_restaurants[] = $restaurant->getId();
										$acl->insertObjectAce($securityIdentity, $mask);
									}
								}

							} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
								$acl = $aclProvider->createAcl($objectIdentity);

								if (in_array($restaurant->getId(), $model['restaurants']))
								{
									$available_restaurants[] = $restaurant->getId();
									$acl->insertObjectAce($securityIdentity, $mask);
								}
							}

							$aclProvider->updateAcl($acl);
						}
					//<-- Add permisson VIEW to restaurants
				}


				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $user->getId(),
																		'fullname' => $user->getFullname(), 
																		'username' => $user->getUsername(), 
																		'email' => $user->getEmail(),
																		'restaurants' => $available_restaurants,
																		'roles' => $roles,
																	));
				
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}
	
	/**
	 * @Route(	"api/user/{uid}",
	 *			name="user_ajax_update",
	 *			requirements={"_method" = "PUT", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
	 */
	 public function API_updateAction($uid, Request $request)
	 {
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $uid == $model['id'])
		{
			$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->find($model['id']);
			
			if (!$user)
				return new Response('No user found for id '.$uid, 404, array('Content-Type' => 'application/json'));

			
			if (isset($model['company']) && (int)$model['company'] > 0)
			{
				$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();

				$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find((int)$model['company']);
							
				if (!$company)
					return new Response('Не найдена компаний c id '.(int)$model['company'], 404, array('Content-Type' => 'application/json'));

		    	// currently user
		        $securityIdentity = UserSecurityIdentity::fromAccount($user);
				$aclProvider = $this->get('security.acl.provider');
				
				// clean old Object Ace
				foreach ($companies as $c) {
				    try {

						$acl = $aclProvider->findAcl(ObjectIdentity::fromDomainObject($c), array($securityIdentity));
						
						foreach($acl->getObjectAces() as $a=>$ace)
						{
							if($ace->getSecurityIdentity()->getUsername() == $user->getUsername())
								$acl->deleteObjectAce($a);
						}

						$aclProvider->updateAcl($acl);

				    } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
				        
				    }
				}

				// creating the ACL
      			$objectIdentity = ObjectIdentity::fromDomainObject($company);

			    try {
					$acl = $aclProvider->findAcl($objectIdentity);			        			    
			    } catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
			        $acl = $aclProvider->createAcl($objectIdentity);
			    }
		        // grant owner access
		        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
		        $aclProvider->updateAcl($acl);

			} else {
				$model['company'] = 0;
			}
			
			$validator = $this->get('validator');
			
			if (isset($model['fullname']) )
				$user->setFullname($model['fullname']);
			
			if (isset($model['username']))
				$user->setUsername($model['username']);
			
			if (isset($model['email']))
				$user->setEmail($model['email']);
			
			$errors = $validator->validate($user);
			
			if (count($errors) > 0)
			{
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
				
				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
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
				
				$result = array('code'=> 200, 'data' => array(	'fullname' => $user->getFullname(),
																'username' => $user->getUsername(), 
																'email' => $user->getEmail(),
																'company' => $model['company'], ));

				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		
		return new Response('Некорректный запрос', 400, array('Content-Type' => 'application/json'));	 
	 }
	 
	/**
	 * @Route(	"api/company/{cid}/user.{_format}",
	 *			name="API_user_create_manag",
	 *			requirements={"_method" = "POST", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function API_createManagerAction($cid, Request $request) // create company manager
	{
		$user = $this->get('security.context')->getToken()->getUser();
		
		// check exist this company
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAllRestaurantsByCompany((int)$cid);
		if (!$company)
			throw $this->createNotFoundException('Компания не найдена');

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
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
					
				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
				
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
																			'ROLE_RESTAURANT_DIRECTOR',
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
				
				//--> Add permisson VIEW to curent company
				$securityIdentity = UserSecurityIdentity::fromAccount($new_user);
				$objectIdentity = ObjectIdentity::fromDomainObject($company);
				$aclProvider = $this->get('security.acl.provider');

				$builder = new MaskBuilder();
				$builder->add('view');
				$mask = $builder->get();  // create only view mask

				try {
					$acl = $aclProvider->findAcl($objectIdentity);			        			    
				} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
					$acl = $aclProvider->createAcl($objectIdentity);
				}

				$acl->insertObjectAce($securityIdentity, $mask); // add view access
				$aclProvider->updateAcl($acl);
				//<-- Add permisson VIEW to curent company
				
				$available_restaurants = array();
				if (isset($model['restaurants']) && is_array($model['restaurants']))
				{
					//--> Add permisson VIEW to restaurants
					$restaurants = $company->getRestaurants();
					
					if ($restaurants)
						foreach ($restaurants as $restaurant)
						{
							$objectIdentity = ObjectIdentity::fromDomainObject($restaurant);
							
							$builder = new MaskBuilder();
							$builder->add('view');
							$mask = $builder->get();  // create only view mask
							
							try {
								$acl = $aclProvider->findAcl($objectIdentity);
							} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
								$acl = $aclProvider->createAcl($objectIdentity);
							}

							if (in_array($restaurant->getId(), $model['restaurants']))
							{
								$available_restaurants[] = $restaurant->getId();
								$acl->insertObjectAce($securityIdentity, $mask);
							}

							$aclProvider->updateAcl($acl);
						}
					//<-- Add permisson VIEW to restaurants
				}
				
				$result = array(	'code' => 200, 'data' => array(	'id' => $new_user->getId(),
																	'fullname' => $new_user->getFullname(), 
																	'username' => $new_user->getUsername(), 
																	'email' => $new_user->getEmail(),
																	'restaurants' => $available_restaurants,
																	'roles' => $roles,
																	));
				
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}

	/**
	 * @Route(	"api/user.{_format}",
	 *			name="API_user_create",
	 *			requirements={"_method" = "POST", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
	 */
	public function API_createAction(Request $request) // create company admin
	{
		$model = (array)json_decode($request->getContent());
		
		if ( count($model) > 0 && isset($model['fullname']) && isset($model['username']) && isset($model['password']) )
		{
			$role_id = 'ROLE_COMPANY_ADMIN';
			
			$role = $this->getDoctrine()->getRepository('AcmeUserBundle:Role')->findOneBy(array('role'=>$role_id));
			
			if (!$role)
				return new Response('No role found for id '.$role_id, 404, array('Content-Type' => 'application/json'));
			
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
					
				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
				
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
						return new Response('No company found for id '.(int)$model['company'], 404, array('Content-Type' => 'application/json'));
					
					// creating the ACL
          			$objectIdentity = ObjectIdentity::fromDomainObject($company);
            		$aclProvider = $this->get('security.acl.provider');

					try {
						$acl = $aclProvider->findAcl($objectIdentity);
					} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {
						$acl = $this->get('security.acl.provider')->createAcl($objectIdentity);
					}
					
			    	// currently user
			        $securityIdentity = UserSecurityIdentity::fromAccount($user);

			        // grant owner access
			        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
			        $aclProvider->updateAcl($acl);

				} else {
					$model['company'] = 0;
				}
				
				$result = array(	'code' => 200, 'data' => array(		'id' => $user->getId(),
																		'fullname' => $user->getFullname(), 
																		'username' => $user->getUsername(), 
																		'email' => $user->getEmail(),
																		'company' => (int)$model['company'], ));
				
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}
	
	/**
	 * @Route(	"api/company/{cid}/user/{uid}.{_format}",
	 *			name="API_user_delete_manag",
	 *			requirements={"_method" = "DELETE", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function API_deleteManagerAction($cid, $uid, Request $request)
	{
		$curent_user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));
		
		$user = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->find($uid);
					
		if (!$user)
			return new Response('No user found for id '.$uid, 404, array('Content-Type' => 'application/json'));
		

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($user);
		$em->flush();
		
		$result = array('code' => 200, 'data' => $uid);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}

	
	/**
	 * @Route(	"api/user/{uid}.{_format}", 
	 * 			name="API_user_delete", 
	 * 			requirements={"_method" = "DELETE", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_SUPER_ADMIN")
	 */
	public function API_deleteAction($uid, Request $request)
	{
		$user = $this->getDoctrine()
					->getRepository('AcmeUserBundle:User')
					->find($uid);
				
		if (!$user)
			return new Response('Не найден пользователь с id '.$uid, 404, array('Content-Type' => 'application/json'));
		

		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($user);
		$em->flush();
		
		$result = array('code' => 200, 'data' => $uid);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	
	
	/**
	 * @Route(	"/", name="start_page" )
	 * @Template()
	 * @Secure(roles="ROLE_RESTAURANT_ADMIN, ROLE_ORDER_MANAGER, ROLE_COMPANY_ADMIN, ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_USER")
	 */
	public function indexAction(Request $request)
	{
        $request = $this->getRequest();
        $session = $request->getSession();
		
		$restaurants_array = array();

		$user = $this->get('security.context')->getToken()->getUser();

		$roles = $user->getRoles();

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ( count($roles) == 1 && $roles[0]->getRole() == 'ROLE_USER')
			{
				return $this->redirect($this->generateUrl('my_calendar'));
			}

		$ROLE_ADMIN = 0;
		if ($this->get('security.context')->isGranted('ROLE_ADMIN'))
			$ROLE_ADMIN = 1;
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$securityContext = $this->get('security.context');
			
			$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();	
			$available_companies = array();

			foreach ($companies as $c) {
				if (false !== $securityContext->isGranted('VIEW', $c))
					$available_companies[] = $c;
			}
			//echo '<pre>'.count($available_companies).'<hr>';	var_dump($available_companies); die;

			if (count($available_companies) == 0)
				throw new AccessDeniedHttpException('Нет доступа');
			else
			{
				$company = $available_companies[0];

				$restaurants = $this->getAvailableRestaurantsAction($company->getId());

				if ($restaurants)
					foreach ($restaurants as $r)
						$restaurants_array[$r->getId()] = $r->getName();

				return array('cid' => $company->getId(), 'ROLE_ADMIN'=>$ROLE_ADMIN, 'company' => $company, 'restaurants_list' => $restaurants_array);
			}
		}
		
		if ($this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();
			return $this->render('AcmeUserBundle:User:index_super_admin.html.twig', array(	'companies' => $companies, 'cid' => 1	));
		}
		else
			return array('ROLE_ADMIN'=>$ROLE_ADMIN, 'company' => false, 'cid' => 0, 'restaurants_list' => $restaurants_array);
	}
	
	
    /**
     * @Route(	"api/company/{cid}/user.{_format}",
	 *			name="API_user_management",
	 *			requirements={"_method" = "GET", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"} )
     * @Template()
	 * @Secure(roles="ROLE_COMPANY_ADMIN, ROLE_RESTAURANT_DIRECTOR, ROLE_RESTAURANT_ADMIN")
     */
    public function API_listByCompanyAction($cid, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();
		
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->checkCompanyAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		if (!$company)
			return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
		
		$available_roles = $this->getDoctrine()->getRepository('AcmeUserBundle:Role')->findBy(array('role' => array('ROLE_RESTAURANT_DIRECTOR', 
																													'ROLE_RESTAURANT_ADMIN',
																													'ROLE_ORDER_MANAGER',
																													'ROLE_ADMIN'))); // available roles

		if ($available_roles)
		{
			foreach ($available_roles AS $r)
				$roles_array[] = array( 'id' => $r->getId(),
										'name' => $r->getName(),
										'role' => $r->getRole(), );
				
		}


		$users_array = array();
		$aclProvider = $this->get('security.acl.provider');
		$objectIdentity = ObjectIdentity::fromDomainObject($company);

		try {
			$acl = $aclProvider->findAcl($objectIdentity);

			if (count($acl->getObjectAces()) > 0)
			{
				foreach($acl->getObjectAces() as $a=>$ace)
				{
					$available_role = true;

					$employee = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->findOneByUsername($ace->getSecurityIdentity()->getUsername());
					if ($employee)
					{
						foreach ($employee->getRoles() AS $r)
							if ($r->getRole() == 'ROLE_COMPANY_ADMIN')
								$available_role = false;

						if ($available_role)
						{
							$restaurants = array();
							$all_company_restaurants = $company->getRestaurants();
							if ($all_company_restaurants)
							{
								foreach ($all_company_restaurants as $restaurant)
								{
									$objectIdentity = ObjectIdentity::fromDomainObject($restaurant);
							
									try {
										$acl = $aclProvider->findAcl($objectIdentity);

										if (count($acl->getObjectAces()) > 0)
										{
											foreach($acl->getObjectAces() as $a=>$ace)
											{
												if($ace->getSecurityIdentity()->getUsername() == $employee->getUsername()) // only curent user
													$restaurants[] = $restaurant->getId();
											}
										}
									} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {

									}
								}
							}

							$roles = array();
							foreach ($employee->getRoles() AS $r)
								$roles[] = $r->getId();

							$users_array[] = array( 	'id'				=> $employee->getId(),
														'username'			=> $employee->getUsername(), 
														'email'				=> $employee->getEmail(),  
														'company'			=> $cid,
														'fullname'			=> $employee->getFullname(),
														'roles'				=> $roles,
														'restaurants'		=> $restaurants,
													);
						}
					}
				}
			}
		} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {

		}

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0
	
		$result = array('code' => 200, 'data' => $users_array);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	
    /**
     * @Route("/company/{cid}/user", name="user_management", requirements={"_method" = "GET"})
     * @Template()
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
     */
    public function listByCompanyAction($cid, Request $request)
    {
		$user = $this->get('security.context')->getToken()->getUser();
		
		$restaurants_array = array();

		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		if (!$company)
			throw $this->createNotFoundException('Компания не найдена');

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			if ($this->checkCompanyAction($cid))
				throw new AccessDeniedHttpException('Нет доступа к компании');

			$restaurants = $this->getAvailableRestaurantsAction($cid);

			if ($restaurants)
				foreach ($restaurants as $r)
					$restaurants_array[$r->getId()] = $r->getName();
		}

		
		$available_roles = $this->getDoctrine()->getRepository('AcmeUserBundle:Role')->findBy(array('role' => array(	'ROLE_RESTAURANT_DIRECTOR', 
																														'ROLE_RESTAURANT_ADMIN',
																														'ROLE_ORDER_MANAGER',
																														'ROLE_ADMIN'))); // available roles

		if ($available_roles)
		{
			foreach ($available_roles AS $r)
				$roles_array[] = array( 'id' => $r->getId(),
										'name' => $r->getName(),
										'role' => $r->getRole(), );
				
		}

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0

		return array('company' => $company, 'restaurants_list' => $restaurants_array );
	}
	
	
	/**
	 * @Route(	"/registration", name="registr")
	 * @Template()
	 */
	public function registrationAction(Request $request)
	{
		$user = new User();
		$form = $this->createForm(new UserType(), $user);
		$errorMessage = array();
		$error = '';

		$role = $this->getDoctrine()->getRepository('AcmeUserBundle:Role')->findOneByRole('ROLE_USER');
		if (!$role)
			throw $this->createNotFoundException('Роль не найдена');

		$user->addRole($role);

		if ($request->getMethod() == 'POST')
		{
			$validator = $this->get('validator');
			$form->bindRequest($request);
			
			if ($form->isValid()) {
			
				$user = $form->getData();

				$check_username = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->findByUsername($user->getUsername());
				if ($check_username)
					$error = 'Номер телефона уже используется.';

				$check_email = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->findByEmail($user->getEmail());
				if ($check_email)
					$error .= 'Email уже используется.';
					
				if (strlen($error) == 0)
				{
					$errors = $validator->validate($user);

					if (count($errors) > 0)
					{
						foreach($errors AS $er)
							$errorMessage[] = $er->getMessage();
					}
					else
					{
						$user->setSalt(md5(time()));
						$encoder = new MessageDigestPasswordEncoder('sha1', true, 10);
						$password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
						$user->setPassword($password);
						$user->setActivationCode(md5($password));
						
						$em = $this->getDoctrine()->getEntityManager();
						$em->persist($user);
						$em->flush();
						
						$activation_code = $user->getActivationCode();
						$message = \Swift_Message::newInstance()
								->setSubject('Подтверждение регистрации')
								->setFrom('showstaff.auth@gmail.com')
								->setTo($user->getEmail())
								->setBody('Для подтверждение регистрации пройдите по этой ссылке http://'.$this->get('request')->server->get('HTTP_HOST').'/confirmation/'.$activation_code);
				
						$this->get('mailer')->send($message);
						
						return $this->redirect($this->generateUrl('success_registration'));
					}
				}
			}
			else
			{
				$errors = $validator->validate($user);			

				$errorMessage = array();

				if (count($errors) > 0)
					foreach($errors AS $er)
						$errorMessage[] = $er->getMessage();
			}
		}
		
        return array( 'form' => $form->createView(), 'errorMessage' => $errorMessage, 'error' => $error);
	}
	
	/**
	 * @Route ( "/success/registration", name="success_registration")
	 * @Template()
	 */
	public function successRegistrationAction()
	{
		return array();
	}	
	
	/**
	 * @Route ( "/confirmation/{code}", name="confirmation")
 	 * @Template()
	 */
	public function confirmAction($code, Request $request)
	{
		$success = 0;
		$message = 'Ошибка!';
		
		
		if ($code != '')
		{
			$user = $this->getDoctrine()
							->getRepository('AcmeUserBundle:User')
							->findOneByActivationCode($code);
							
			if (!$user)
			{
				$message = 'Неверный код подтверждения';
			}
			else
			{
				$user->setActive(1);
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($user);
				$em->flush();
				$success = 1;
			}
		}
		return array('error_message'=>$message, 'success' => $success);
	}
	
	/**
	 * @Route(	"api/feedback.{_format}",
	 *			name="API_feedback",
	 *			requirements={	"_method" = "PUT",
	 *							"_format" = "json|xml"},
	 *			defaults={"_format"="json"})
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

			$result = array('code' => 200, 'message'=> 'Успешно отправлено');
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));

		} else
			return new Response('Некорректный запрос', 400, array('Content-Type' => 'application/json'));
	}

	/**
	 * @Route(	"cup/{cid}", name="checkUserPermission", requirements={	"_method" = "GET"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function checkUserPermissionAction($cid)
	{
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find((int)$cid);				
		if (!$company)
			return new Response('Компания не найдена', 404, array('Content-Type' => 'application/json'));

		$all_company_restaurants = $company->getRestaurants();

		$securityContext = $this->get('security.context');
		$aclProvider = $this->get('security.acl.provider');
		
		$users = array();
		try {
			
			$acl = $aclProvider->findAcl(ObjectIdentity::fromDomainObject($company));

			if (count($acl->getObjectAces()) > 0)
			{
				foreach($acl->getObjectAces() as $ace)
				{		
					$roles = array();
					$restaurants = array();
					$employee = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->findOneByUsername($ace->getSecurityIdentity()->getUsername());
					if ($employee)
					{
						foreach ($employee->getRoles() AS $r)
							$roles[$r->getRole()] = $r->getName();

						if ($all_company_restaurants)
							foreach ($all_company_restaurants as $restaurant)
							{
								$objectIdentity = ObjectIdentity::fromDomainObject($restaurant);
						
								try {
									$acl = $aclProvider->findAcl($objectIdentity);

									if (count($acl->getObjectAces()) > 0)
									{
										foreach($acl->getObjectAces() as $a=>$ace)
										{
											if($ace->getSecurityIdentity()->getUsername() == $employee->getUsername()) // only curent user
												$restaurants[$restaurant->getId()] = $restaurant->getName();
										}
									}
								} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {

								}
							}
					}
					echo '<strong>'.$employee->getUsername().'</strong>';
					$users[$employee->getUsername()] = array('restaurants'=>$restaurants, 'roles' => $roles);
					//echo 	'<table border=1><tr><td>restaurants</td><td>roles</td></tr><tr><td>';
					var_dump($restaurants);
					//echo 	'</td><td>';
					var_dump($roles);
					echo 	'<hr>';
				}
			}
			else
			{
				echo 'Нет сотрудников';
			}

		} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {

		}
		die;
	}
   public function __construct($container = null)
    {
        $this->container = $container;
        // ... deal with any more arguments etc here
    }

    public function get($service)
    {
        return $this->container->get($service);
    }

	public function checkCompanyAction($cid)
	{
		$securityContext = $this->get('security.context');
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();	
		$available_companies = array();

		foreach ($companies as $c)
			if (false !== $securityContext->isGranted('VIEW', $c))
				$available_companies[$c->getId()] = $c;

		if (count($available_companies) == 0  || !array_key_exists($cid, $available_companies))
			return 1;
		else
			return 0;
	}


	public function checkCompanyEditAction($cid)
	{
		$securityContext = $this->get('security.context');
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();	
		$available_companies = array();

		foreach ($companies as $c)
			if (false !== $securityContext->isGranted('EDIT', $c))
				$available_companies[$c->getId()] = $c;

		if (count($available_companies) == 0  || !array_key_exists($cid, $available_companies))
			return 1;
		else
			return 0;
	}

	public function checkCompanyDeleteAction($cid)
	{
		$securityContext = $this->get('security.context');
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();	
		$available_companies = array();

		foreach ($companies as $c)
			if (false !== $securityContext->isGranted('DELETE', $c))
				$available_companies[$c->getId()] = $c;

		if (count($available_companies) == 0  || !array_key_exists($cid, $available_companies))
			return 1;
		else
			return 0;
	}

	public function getAvailableRestaurantsAction($cid)
	{
		$restaurants = $this->getDoctrine()->getRepository('SupplierBundle:Restaurant')->findByCompany($cid);
		$user = $this->get('security.context')->getToken()->getUser();
		$aclProvider = $this->get('security.acl.provider');
		$available_restaurants = array();
		if (!$this->get('security.context')->isGranted('ROLE_COMPANY_ADMIN'))
		{
			$securityContext = $this->get('security.context');
			foreach ($restaurants as $restaurant)
			{
				$objectIdentity = ObjectIdentity::fromDomainObject($restaurant);
		
				try {
					$acl = $aclProvider->findAcl($objectIdentity);

					if (count($acl->getObjectAces()) > 0)
					{
						foreach($acl->getObjectAces() as $a=>$ace)
						{
							if($ace->getSecurityIdentity()->getUsername() == $user->getUsername()) // only curent user
								$available_restaurants[] = $restaurant;
						}
					}
				} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {

				}
			}
		}
		else
		{
			$available_restaurants = $restaurants;
		}

		return $available_restaurants;
	}
	public function getAvailableCompaniesAction()
	{
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();



		$user = $this->get('security.context')->getToken()->getUser();
		$aclProvider = $this->get('security.acl.provider');
		$available_companies = array();
		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
		{
			$securityContext = $this->get('security.context');
			foreach ($companies as $company)
			{
				$objectIdentity = ObjectIdentity::fromDomainObject($company);
				//var_dump($objectIdentity); die;
				try {
					$acl = $aclProvider->findAcl($objectIdentity);

					if (count($acl->getObjectAces()) > 0)
					{
						foreach($acl->getObjectAces() as $a=>$ace)
						{
							if($ace->getSecurityIdentity()->getUsername() == $user->getUsername()) // only curent user
								$available_companies[] = $company;
						}
					}
				} catch (\Symfony\Component\Security\Acl\Exception\Exception $e) {

				}
			}
		}
		else
		{
			$available_companies = $companies;
		}

		return $available_companies;
	}
}
