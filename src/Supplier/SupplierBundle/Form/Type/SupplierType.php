<?php
// src/Supplier/SupplierBundle/Form/Type/ProductType.php

namespace Supplier\SupplierBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class SupplierType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('name');
    }

    public function getName()
    {
        return 'supplier';
    }
    
}
