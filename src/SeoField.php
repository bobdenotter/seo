<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Storage\EntityManager;
use Bolt\Storage\Field\Type\FieldTypeBase;
use Bolt\Storage\QuerySet;

class SeoField extends FieldTypeBase
{
    public function getName()
    {
        return 'seo';
    }

    public function getStorageType()
    {
        return 'text';
    }

    public function getTemplate()
    {
        return '_seo_extension_field.twig';
    }

    public function getStorageOptions()
    {
        return [
          'default' => null,
          'notnull' => false
        ];
    }
}

