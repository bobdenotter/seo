<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Extension\SimpleExtension;
//use Bolt\Translation\Translator as Trans;
use Silex\Application;
//use Symfony\Component\Translation\Loader as TranslationLoader;


class SeoExtension extends SimpleExtension
{
    private $version = "v0.10.0";

    public function registerFields()
    {
        return [
            new SeoField(),
        ];
    }

    public function registerServices(Application $app)
    {
        $seo = new SEO($this->getContainer(), $this->getConfig(), $this->version);
        $app['twig']->addGlobal('seo', $seo);
        $app['twig']->addGlobal('seoconfig', $this->getConfig());
    }

    protected function registerTwigPaths()
    {
        return [
            'templates' => ['position' => 'prepend', 'namespace' => 'bolt']
        ];
    }

    protected function registerAssets()
    {
        $seoCss = new Stylesheet();
        $seoCss->setFileName('seo.css')->setZone(Zone::BACKEND);

        $underscoreJs = new JavaScript();
        $underscoreJs->setFilename('underscore-min.js')->setZone(Zone::BACKEND)->setPriority(10);

        $backboneJs = new JavaScript();
        $backboneJs->setFilename('backbone-min.js')->setZone(Zone::BACKEND)->setPriority(15);

        return [
            $seoCss,
            $backboneJs,
            $underscoreJs,

        ];
    }

    public function initialize() {

        $currentUser    = $this->app['users']->getCurrentUser();
        $currentUserId  = $currentUser['id'];

        // Set the Permissions for the advanced field to the correct roles.
        $this->config['allowed'] = array();
        if (!empty($this->config['allow'])) {
            foreach ($this->config['allow'] as $key => $field) {
                $this->config["allowed"][$key] = false;
                foreach ($this->config['allow'][$key] as $role) {
                    if ($this->app['users']->hasRole($currentUserId, $role)) {
                        $this->config["allowed"][$key] = true;
                        break;
                    }
                }
            }
        }

        $this->app['twig']->addGlobal('seoconfig', $this->config);
    }

    public function before()
    {
        $this->translationDir = __DIR__.'/locales/' . substr($this->app['locale'], 0, 2);
        if (is_dir($this->translationDir))
        {
            $iterator = new \DirectoryIterator($this->translationDir);
            foreach ($iterator as $fileInfo)
            {
                if ($fileInfo->isFile())
                {
                    $this->app['translator']->addLoader('yml', new TranslationLoader\YamlFileLoader());
                    $this->app['translator']->addResource('yml', $fileInfo->getRealPath(), $this->app['locale']);
                }
            }
        }
    }

}
