<?php

namespace Acme\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Acme\UserBundle\Entity\Duty;

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
}
