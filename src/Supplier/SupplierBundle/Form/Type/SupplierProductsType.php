<?php
// src/Supplier/SupplierBundle/Form/Type/SupplierProductsType.php

namespace Supplier\SupplierBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Doctrine\ORM\EntityRepository;

class SupplierProductsType extends AbstractType
{
	private $category;
	
    public function buildForm(FormBuilder $builder, array $options)
    {
		
		 
										
		$builder->add('supplier_name', 'text', array('required'  => true));

		$builder->add('prime', 'choice', array(
			'choices' => array('1' => 'Первичный', '0' => 'Вторичный'),
			'preferred_choices' => array('0'),
		));
											
		$builder->add('price',  'money', array(
											'divisor' => 100, 
											'required'  => true));
		
		$builder->add('product','entity', array( 
											'required'    => true,
											'class' => 'SupplierBundle:Product',	
											'property' => 'name',
											));
											
		$builder->add('supplier','entity', array( 
											'required'    => true,
											'class' => 'SupplierBundle:Supplier',	
											'property' => 'name',
											));

    }
	
    
    public function getName()
    {
        return 'training';
    }
}
