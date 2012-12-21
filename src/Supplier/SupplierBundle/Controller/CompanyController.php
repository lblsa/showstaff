<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Company;
use Supplier\SupplierBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\CompanyType;
use JMS\SecurityExtraBundle\Annotation\Secure;


class CompanyController extends Controller
{
	
	/**
	 * @Route(	"company", name="company",	requirements={"_method" = "GET"})
	 * @Template()
     * @Secure(roles="ROLE_SUPER_ADMIN")
	 */
	public function listAction(Request $request)
	{
		$companies = $this->getDoctrine()->getRepository('SupplierBundle:Company')->findAll();

		$companies_array = array();

		if ($companies)
		{
			foreach ($companies AS $p)
				$companies_array[] = array( 	'id' => $p->getId(),
												'name'=> $p->getName(), 
												'extended_name' => $p->getExtendedName(),
												'inn' => $p->getInn(),
											);
		}
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0

		return array( 'companies_json' => json_encode($companies_array) );
	}
	
	/**
	 * @Route(	"api/company.{_format}", 
	 *			name="API_company", 
	 *			requirements={"_method" = "GET", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Template()
     * @Secure(roles="ROLE_SUPER_ADMIN, ROLE_USER, ROLE_COMPANY_ADMIN")
	 */
	public function API_listAction(Request $request)
	{
		$companies = $this->get("my.user.service")->getAvailableCompaniesAction();

		$companies_array = array();
		
		if ($companies)
		{
			foreach ($companies AS $p)
				$companies_array[] = array( 	'id' => $p->getId(),
												'name'=> $p->getName(), 
												'extended_name' => $p->getExtendedName(),
												'inn' => $p->getInn() );
		}
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");// дата в прошлом
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // всегда модифицируется
		header("Cache-Control: no-store, no-cache, must-revalidate");// HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");// HTTP/1.0

		$code = 200;
		$result = array('code' => $code, 'data' => $companies_array);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}	
	
	/**
	 * @Route(	"api/company/{cid}.{_format}",
	 *			name="API_company_update",
	 *			requirements={"_method" = "PUT", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_SUPER_ADMIN, ROLE_USER, ROLE_COMPANY_ADMIN")
	 */
	 public function API_updateAction($cid, Request $request)
	 {	 
		$model = (array)json_decode($request->getContent());

		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		if (!$company)
			return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyEditAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));

		if (count($model) > 0 && isset($model['id']) && is_numeric($model['id']) && $cid == $model['id'])
		{
			$company = $this->getDoctrine()
							->getRepository('SupplierBundle:Company')
							->find($model['id']);
			
			if (!$company)
				return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));
			
			$validator = $this->get('validator');

			$company->setName($model['name']);
			$company->setExtendedName($model['extended_name']);
			$company->setInn($model['inn']);
			
			$errors = $validator->validate($company);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
		
				return new Response(implode(', ', $errorMessage), 400, array('Content-Type' => 'application/json'));
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($company);
				$em->flush();
				
				$code = 200;
				$result = array('code'=> $code, 'data' => array(	'name' => $company->getName(),
																	'extended_name' => $company->getExtendedName(), 
																	'inn' => $company->getInn()
																));
				return new Response(json_encode($result), $code, array('Content-Type' => 'application/json'));		
			}
		}

		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));		 
	 }
	 

	/**
	 * @Route(	"api/company.{_format}", 
	 *			name="API_company_create", 
	 *			requirements={"_method" = "POST", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_SUPER_ADMIN, ROLE_USER, ROLE_COMPANY_ADMIN")
	 */
	public function API_createAction(Request $request)
	{
		$model = (array)json_decode($request->getContent());

		if (count($model) > 0 && isset($model['inn']) && isset($model['name']))
		{
			$validator = $this->get('validator');
			$company = new Company();
			$company->setName($model['name']);
			$company->setExtendedName($model['extended_name']);
			$company->setInn((int)$model['inn']);

			$errors = $validator->validate($company);
			
			if (count($errors) > 0) {
				
				foreach($errors AS $error)
					$errorMessage[] = $error->getMessage();
					
				return new Response(implode(', ',$errorMessage), 400, array('Content-Type' => 'application/json'));
				
			} else {
				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($company);
				$em->flush();
				

				if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
				{
					$user = $this->get('security.context')->getToken()->getUser();
					$securityIdentity = UserSecurityIdentity::fromAccount($user);
					$aclProvider = $this->get('security.acl.provider');
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


			        // add role ROLE_COMPANY_ADMIN
			        $role = $this->getDoctrine()->getRepository('AcmeUserBundle:Role')->findOneByRole('ROLE_COMPANY_ADMIN');
					if (!$role)
						throw $this->createNotFoundException('Роль не найдена');
					else
						$user->addRole($role);
				}
				
				$code = 200;
				$result = array(	'code' => $code, 'data' => array(	'id' => $company->getId(),
																		'name' => $company->getName(), 
																		'extended_name' => $company->getExtendedName(), 
																		'inn' => $company->getINN()
																	));
				
				return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
			}
		}
		
		return new Response('Invalid request', 400, array('Content-Type' => 'application/json'));
	}
	
	
	/**
	 * @Route(	"api/company/{cid}.{_format}", 
	 * 			name="API_company_delete", 
	 * 			requirements={"_method" = "DELETE", "_format" = "json|xml"},
	 *			defaults={"_format" = "json"})
	 * @Secure(roles="ROLE_SUPER_ADMIN, ROLE_USER, ROLE_COMPANY_ADMIN")
	 */
	public function API_deleteAction($cid, Request $request)
	{
		$company = $this->getDoctrine()->getRepository('SupplierBundle:Company')->find($cid);
		if (!$company)
			return new Response('No company found for id '.$cid, 404, array('Content-Type' => 'application/json'));

		if (!$this->get('security.context')->isGranted('ROLE_SUPER_ADMIN'))
			if ($this->get("my.user.service")->checkCompanyDeleteAction($cid))
				return new Response('Нет доступа к компании', 403, array('Content-Type' => 'application/json'));


		$em = $this->getDoctrine()->getEntityManager();				
		$em->remove($company);
		$em->flush();
		
		$code = 200;
		$result = array('code' => $code, 'data' => $cid);
		return $this->render('SupplierBundle::API.'.$this->getRequest()->getRequestFormat().'.twig', array('result' => $result));
	}
}
