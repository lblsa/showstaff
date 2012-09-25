<?php

namespace Acme\UserBundle\Controller;

use Acme\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;
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
     * @Template()
     */
    public function logincheckAction()
    {
        return array('name' => ' 1 ILYA');
    }
	
    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction()
    {
    	// The security layer will intercept this request
	}
	
    /**
     * @Route("/user", name="user",	requirements={"_method" = "GET"})
	 * )
     * @Template()
	 * @Secure(roles="ROLE_SUPER_ADMIN")
     */
    public function listAction()
    {
    	$users = $this->getDoctrine()->getRepository('AcmeUserBundle:User')->findAll();
		
		$users_array = array();
		
		if ($users)
		{
			foreach ($users AS $p)
				$users_array[] = array( 	'id' => $p->getId(),
											'username'=> $p->getUsername(), 
											'email'=> $p->getEmail(), 
											'password'=> $p->getPassword(), 
											'fullname' => $p->getFullname(),
											'salt' => $p->getSalt(),
											'roles' => $p->getRoles(),	);
		}

		return array( 'users' => $users, 'users_json' => json_encode($users_array) );
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
			$user->setPassword('');
			$user->setSalt('');
			$user->setUsername($model['username']);
			$user->setEmail($model['email']);
			
			$errors = $validator->validate($user);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
				
				$code = 400;
				$result = array('code'=>$code, 'message'=>$errorMessage);
				$response = new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));
				$response->sendContent();
				die();
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($user);
				$em->flush();
				
				$code = 200;
				
				$result = array('code'=> $code, 'data' => array(	'fullname' => $user->getFullname(),
																	'username' => $user->getUsername(), 
																	'email' => $user->getEmail()
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
		
		if (count($model) > 0 && isset($model['fullname']) && isset($model['username']))
		{
			$validator = $this->get('validator');
			$user = new User();
			$user->setFullname($model['fullname']);
			$user->setPassword('');
			$user->setSalt('');
			$user->setUsername($model['username']);
			$user->setEmail($model['email']);
			
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
	 * @Route(	"user/{uid}", 
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
}
