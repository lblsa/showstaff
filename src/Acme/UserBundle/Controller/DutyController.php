<?php

namespace Acme\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Acme\UserBundle\Entity\Duty;
use JMS\SecurityExtraBundle\Annotation\Secure;

/**
 * Duty controller.
 */
class DutyController extends Controller
{
    /**
     * Lists all Duty entities.
     *
     * @Route(	"api/duty.{_format}",
				name="API_duty",
				requirements={"_method" = "GET", "_format" = "json|xml"},
				defaults={"_format" = "json"})	)
     * @Template()
     */
    public function API_listAction()
    {
		$entities = $this->getDoctrine()->getRepository('AcmeUserBundle:Duty')->findAll();

		$entities_array = array();
		
		if ($entities)
		{
			foreach ($entities AS $p)
				$entities_array[] = array( 	'id' => $p->getId(), 'name'=> $p->getName());
		}
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0

		$code = 200;
		$result = array('code' => $code, 'data' => $entities_array);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
    }
    
    /**
     * Lists Duty entities.
     *
     * @Route(	"duty",
				name="listduty",
				requirements={"_method" = "GET"}	)
     * @Template()
     * @Secure(roles="ROLE_COMPANY_ADMIN")
     */
    public function listAction()
    {
		return array();
    }
    
	/**
	 * @Route(	"api/duty.{_format}", 
	 * 			name="API_duty_create", 
	 * 			requirements={"_method" = "POST", "_format" = "json|xml"},
				defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function API_createAction(Request $request)
	{
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['name']))
		{
			$validator = $this->get('validator');
			$duty = new Duty();
			$duty->setName($model['name']);
			
			$errors = $validator->validate($duty);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();

				return new Response(implode(', ', $errorMessage), 400, array('Content-Type' => 'application/json'));
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($duty);
				$em->flush();
				
				$result = array('code' => 200, 'data' => array(	'id' => $duty->getId(),
																'name' => $duty->getName()
																));
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			
			}
		}
		
		return new Response('Некорректный запрос', 400, array('Content-Type' => 'application/json'));
	}
	
	/**
	 * @Route(	"api/duty/{sid}.{_format}",
				name="API_duty_delete",
				requirements={"_method" = "DELETE", "_format" = "json|xml"},
				defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	public function API_deleteAction($sid, Request $request)
	{		
		$duty = $this->getDoctrine()
					->getRepository('AcmeUserBundle:Duty')
					->find($sid);
					
		if (!$duty)
		{
			$result = array('code' => 200, 'data' => $sid, 'message' => 'Должность не найдена');
			return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
		}
		
		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($duty);
		$em->flush();
		
		
		$result = array('code' => 200, 'data' => $sid);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
	
	/**
	 * @Route(	"api/duty/{sid}.{_format}", 
	 * 			name="API_duty_update", 
	 * 			requirements={"_method" = "PUT", "_format" = "json|xml"},
				defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_COMPANY_ADMIN")
	 */
	 public function API_updateAction($sid, Request $request)
	 { 
		$model = (array)json_decode($request->getContent());
		
		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $sid == $model['id'])
		{
			$duty = $this->getDoctrine()
							->getRepository('AcmeUserBundle:Duty')
							->find($model['id']);
			
			if (!$duty)
				return new Response('Должность не найдена', 404, array('Content-Type' => 'application/json'));
			
			$validator = $this->get('validator');

			$duty->setName($model['name']);
			
			$errors = $validator->validate($duty);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();

				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
								
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($duty);
				$em->flush();
				
				$result = array('code'=> 200, 'data' => array('name' => $duty->getName()));
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			
			}
		}

		return new Response('Некорректный ответ', 400, array('Content-Type' => 'application/json'));
	 }
}
