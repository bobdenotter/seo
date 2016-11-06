<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Storage\Field\FieldInterface;

class SeoField implements FieldInterface
{
    public function getName()
    {
        return 'seo';
    }

    public function getTemplate()
    {
        return '_seo_extension_field.twig';
    }

    public function getStorageType()
    {
        return 'text';
    }

    public function getStorageOptions()
    {
        return ['default' => null, 'notnull' => false];
    }
}
