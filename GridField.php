<?php

namespace Bolt\Extension\BobdenOtter\GridField;

use Bolt\Field\FieldInterface;

class GridField implements FieldInterface
{

    public function getName()
    {
        return 'grid';
    }

    public function getTemplate()
    {
        return '_grid.twig';
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