<?php

namespace Supplier\SupplierBundle\Controller;

use Supplier\SupplierBundle\Entity\Supplier;
use Supplier\SupplierBundle\Entity\Product;
use Supplier\SupplierBundle\Entity\SupplierProducts;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Supplier\SupplierBundle\Form\Type\SupplierProductsType;

class SupplierProductsController extends Controller
{
	public $unit = array(	'1' => 'кг',
							'2' => 'литр',
							'3' => 'шт',
							'4' => 'пучок',
							'5' => 'бутылка',);
	
	
	
    /**
     * @Route("/supplier/products/del/{id}", name="supplier_products_del")
     */
    public function delAction($id)
    {
		$supplier_product = $this->getDoctrine()
						->getRepository('SupplierBundle:SupplierProducts')
						->find($id);
						
		if (!$supplier_product) {
			throw $this->createNotFoundException('No Supplier Products found for id '.$id);
		}
		
		$em = $this->getDoctrine()->getEntityManager();					
		$em->remove($supplier_product);
		$em->flush();
			
        return $this->redirect($this->generateUrl('supplier_products_list'));
    }
	
	 /**
     * @Route("/supplier/products/create", name="supplier_products_create")
     * @Template()
     */    
    public function createAction(Request $request)
    {
		$supplier_product = new SupplierProducts();
		
		$form = $this->createForm(new SupplierProductsType(), $supplier_product);
					
		if ($request->getMethod() == 'POST')
		{			
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$supplier_product = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier_product);
				$em->flush();
				return $this->redirect($this->generateUrl('supplier_products_list'));
			}
		}


		return array('form' => $form->createView());
	}
	 /**
     * @Route("/supplier/products/edit/{id}", name="supplier_products_edit")
     * @Template()
     */    
    public function editAction($id, Request $request)
    {
		$supplier_product = $this->getDoctrine()
						->getRepository('SupplierBundle:SupplierProducts')
						->find($id);
						
		$form = $this->createForm(new SupplierProductsType(), $supplier_product);
					
		if ($request->getMethod() == 'POST')
		{			
			$validator = $this->get('validator');
			$form->bindRequest($request);

			if ($form->isValid())
			{
				$supplier_product = $form->getData();				
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($supplier_product);
				$em->flush();
				return $this->redirect($this->generateUrl('supplier_products_list'));
			}
		}


		return array('form' => $form->createView(), 'id' => $id);
	}
	
	/**
	 * @Route("/supplier/products/list", name="supplier_products_list")
	 * @Template()
	 */
	public function listAction()
    {
		
		$supplier_products = $this->getDoctrine()
					->getRepository('SupplierBundle:SupplierProducts')
					->get();
					
		return array('supplier_products' => $supplier_products, 'unit' => $this->unit);
	}
}
