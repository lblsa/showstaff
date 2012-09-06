<?php
// src/Supplier/SupplierBundle/Form/Type/RestaurantType.php

namespace Supplier\SupplierBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Doctrine\ORM\EntityRepository;

class RestaurantType extends AbstractType
{
	private $category;
	
    public function buildForm(FormBuilder $builder, array $options)
    {
		
		$builder->add('name', 'text', array('required'  => true));
		$builder->add('address', 'text', array('required'  => false));
		$builder->add('director', 'text', array('required'  => false));

    }
	
    
    public function getName()
    {
        return 'restaurant';
    }
}
