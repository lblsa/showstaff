<?php
// src/Supplier/SupplierBundle/Form/Type/ProductType.php

namespace Supplier\SupplierBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name', 'text', array('required'  => true,));
        $builder->add('extended_name', 'textarea', array('required'  => false,));
        $builder->add('inn','text', array('required'  => true,));
    }

    public function getName()
    {
        return 'company';
    }
    
}
