<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\ProductType;

class ProductController extends Controller
{
	public $unit = array(	'1' => 'кг',
							'2' => 'литр',
							'3' => 'шт',
							'4' => 'пучок',
							'5' => 'бутылка',);
	
    /**
     * @Route("/product/index/{name}")
     * @Template()
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }
    

    /**
     * @Route("/product/create", name="product_create")
     * @Template()
     */    
    public function createAction(Request $request)
    {
		$product = new Product();
		
		$form = $this->createForm(new ProductType($this->unit), $product);
					
		if ($request->getMethod() == 'POST')
		{			
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$product = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($product);
				$em->flush();
				return $this->redirect($this->generateUrl('product_list'));
			}
		}


		return array('form' => $form->createView());
	}
	
	
    /**
     * @Route("/product/edit/{id}", name="product_edit")
     * @Template()
     */    
	public function editAction($id, Request $request)
	{
		$product = $this->getDoctrine()
						->getRepository('SupplierBundle:Product')
						->find($id);
		
		$form = $this->createForm(new ProductType($this->unit), $product);
					
		if ($request->getMethod() == 'POST')
		{
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$product = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($product);
				$em->flush();
				return $this->redirect($this->generateUrl('product_list'));
			}
		}


		return array('form' => $form->createView(), 'product' => $product);
	}
	
	
	/**
	 * @Route("/product/list", name="product_list")
	 * @Template()
	 */
	public function listAction()
	{
		$repository = $this->getDoctrine()->getRepository('SupplierBundle:Product');
		$products = $repository->findAll();
		
		return array( 'products' => $products, 'unit' => $this->unit);
	}
}
