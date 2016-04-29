<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Controller\Zone;
use Bolt\Extension\SimpleExtension;
//use Bolt\Translation\Translator as Trans;
//use Symfony\Component\Translation\Loader as TranslationLoader;

//require_once('../Seo.php');
//require_once('./SEO.php');

class SeoExtension extends SimpleExtension
{
    private $version = "v0.10.0";

    public function registerFields()
    {
        return [
            new SeoField(),
        ];
    }

    protected function registerTwigPaths()
    {
        return [
            'templates' => ['position' => 'prepend', 'namespace' => 'bolt']
        ];
    }

    protected function registerTwigFunctions()
    {
        $seo = new SEO($this->getContainer(), $this->getConfig(), $this->version);

        return [
            'seo' =>  'seoObject',
            'seoconfig' => 'seoConfig',
        ];
    }

    public function SeoConfig()
    {
        return $this->getConfig();
    }

    public function SeoObject()
    {
        return new SEO($this->getContainer(), $this->getConfig(), $this->version);
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

        $end = $this->app['config']->getWhichEnd();

        if ($end =='backend') {

            $this->app->before(array($this, 'before'));

            $this->app['htmlsnippets'] = true;

            $this->addCss('assets/seo.css');
            // $this->addJavascript('assets/seo.js', true);
        }

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
