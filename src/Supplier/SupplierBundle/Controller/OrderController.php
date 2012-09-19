<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
//use Supplier\SupplierBundle\Form\Type\BookingType;

class OrderController extends Controller
{
	
    /**
     * @Route("company/{cid}/order", name="booking_list")
     */
    public function listAction($cid, $pid, Request $request)
    {
		
	}
	
}
