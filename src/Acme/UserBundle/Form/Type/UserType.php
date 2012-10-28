<?php
// src/Acme/UserBundle/Form/Type/UserType.php

namespace Acme\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class UserType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('username', null, array('required'=>true, 'label'=>'Телефон'));
        $builder->add('fullname', null, array('required'=>false, 'label'=>'ФИО'));
        $builder->add('email', 'email', array('required'=>true, 'label'=>'Эл. почта'));
        $builder->add('password', 'password', array('required'=>true, 'label'=>'Пароль'));
    }
    
    public function getName()
    {
        return 'user';
    }
}
