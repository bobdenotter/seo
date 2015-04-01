<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Field\FieldInterface;

class SEOField implements FieldInterface
{

    public function getName()
    {
        return 'seo';
    }

    public function getTemplate()
    {
        return '_seo.twig';
    }

    public function getStorageType()
    {
        return 'text';
    }

    public function getStorageOptions()
    {
        return array('default'=>'');
    }

}