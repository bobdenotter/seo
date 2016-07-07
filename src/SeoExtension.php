<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Widget\Widget;
use Bolt\Controller\Zone;
use Bolt\Extension\SimpleExtension;
//use Bolt\Translation\Translator as Trans;
use Silex\Application;
//use Symfony\Component\Translation\Loader as TranslationLoader;


class SeoExtension extends SimpleExtension
{
    private $version = "v0.10.2";

    private $eastereggchance = 0.1;

    public function registerFields()
    {
        return [
            new SeoField(),
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

        $assets = [
            $seoCss,
            $backboneJs,
            $underscoreJs,
        ];

        // Perhaps we show the widget on the dashboard.
        $rollthedice = (float)rand() / (float)getrandmax();
        if ($rollthedice < $this->eastereggchance) {
            $widgetObj = new Widget();
            $widgetObj
                ->setZone('backend')
                ->setLocation('dashboard_aside_middle')
                ->setCallback([$this, 'backendDashboardWidget'])
                ->setCallbackArguments([])
                ->setDefer(false);
            $assets[] = $widgetObj;
        }

        return $assets;
    }


    public function backendDashboardWidget()
    {
        $images = [
            'https://media4.giphy.com/media/tIeCLkB8geYtW/giphy.gif',
            'https://media.giphy.com/media/tITfss8cqzTO0/giphy.gif',
            'https://media.giphy.com/media/jQqU9dCKUOdri/giphy.gif',
            'https://media.giphy.com/media/UmayQNXJCo86I/giphy.gif',
            'https://media.giphy.com/media/11ISwbgCxEzMyY/giphy.gif',
            'https://media4.giphy.com/media/14bhmZtBNhVnIk/giphy.gif',
            'https://media0.giphy.com/media/geYwtodB9AiI0/giphy.gif',
            'https://media3.giphy.com/media/3o7abB06u9bNzA8lu8/giphy.gif'
        ];
        $image = $images[array_rand($images)];

        // Render the template, and return the results
        return $this->renderTemplate('widget_easteregg.twig', [
            'version' => $this->version,
            'image' => $image
        ]);
    }

    public function registerServices(Application $app)
    {
        $app['twig'] = $app->extend(
            'twig',
            function ($twig) use ($app) {
                    $seo = new SEO($this->getContainer(), $this->getConfig(), $this->version);
                    $twig->addGlobal('seo', $seo);
                    $twig->addGlobal('seoconfig', $this->getConfig());

                    return $twig;
            }
        );
    }

    protected function registerTwigPaths()
    {
        return [
            'templates' => ['position' => 'prepend', 'namespace' => 'bolt']
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

    protected function getDefaultConfig()
    {
        return [
            'templates' => [
                'meta' => '@bolt/_metatags.twig',
            ]
        ];
    }
}
