<?php
// src/Supplier/SupplierBundle/Form/Type/ProductType.php

namespace Supplier\SupplierBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class ProductType extends AbstractType
{
	/*
	 * Available unit
	 * 
	 * @var array $unit
	 */
	private $unit;
	
	
	function __construct($unit)
	{
		$this->unit = $unit;
	}

	
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name');
        $builder->add('unit','choice', array(
												'choices'   => $this->unit,
												'required'  => true,)
					);
    }

    public function getName()
    {
        return 'product';
    }
    
}
