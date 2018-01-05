<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Widget\Widget;
use Bolt\Controller\Zone;
use Bolt\Extension\SimpleExtension;
use Silex\Application;

class SeoExtension extends SimpleExtension
{
    private $version = 'v1.0.3';

    public function registerFields()
    {
        return [
            new SeoField(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerAssets()
    {
        $seoCss = new Stylesheet();
        $seoCss->setFileName('seo.css')->setZone(Zone::BACKEND);

        $underscoreJs = new JavaScript();
        $underscoreJs->setFileName('underscore-min.js')->setZone(Zone::BACKEND)->setPriority(10);

        $backboneJs = new JavaScript();
        $backboneJs->setFileName('backbone-min.js')->setZone(Zone::BACKEND)->setPriority(15);

        $assets = [
            $seoCss,
            $backboneJs,
            $underscoreJs,
        ];

        $config = $this->getConfig();

        // Perhaps we show the Easter Egg widget on the dashboard.
        if (!$config['disableeasteregg']) {
            $rollthedice = (float) rand() / (float) getrandmax();
            if ($rollthedice < $config['eastereggchance']) {
                $widgetObj = new Widget();
                $widgetObj
                    ->setCallback([$this, 'backendDashboardWidget'])
                    ->setCallbackArguments([])
                    ->setDefer(false)
                    ->setZone('backend')
                    ->setLocation('dashboard_aside_middle')
                ;
                $assets[] = $widgetObj;
            }
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
            'https://media3.giphy.com/media/3o7abB06u9bNzA8lu8/giphy.gif',
        ];
        $image = $images[array_rand($images)];

        // Render the template, and return the results
        return $this->renderTemplate('widget_easteregg.twig', [
            'version' => $this->version,
            'image'   => $image,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices(Application $app)
    {
        $app['twig'] = $app->extend(
            'twig',
            function (\Twig_Environment $twig) use ($app) {
                $config = $this->getConfig();

                $seo = new SEO($app, $config, $this->version);
                $twig->addGlobal('seo', $seo);
                $twig->addGlobal('seoconfig', $config);

                return $twig;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return [
            'templates' => ['position' => 'prepend', 'namespace' => 'bolt'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        return [
            'seoAllowedConfig' => 'seoAllowedConfig',
        ];
    }

    /**
     * Returns an array with permissions whether to show certain advanced fields
     * or not for the current user.
     *
     * @return array
     */
    public function seoAllowedConfig()
    {
        $config = $this->getConfig();

        /** @var \Bolt\Users $users */
        $users = $this->getContainer()['users'];

        $currentUser    = $users->getCurrentUser();
        $currentUserId  = $currentUser['id'];

        // A permission is `true` if:
        // (1) the `$key` is defined and the value for `$key` is `true`, or
        // (2) the `$key` is not defined (default)
        $allowedConfig = [
            'shortlink' => true,
            'canonical' => true,
            'robots'    => true,
            'ogtype'    => true,
        ];

        foreach ($allowedConfig as $key => $value) {
            if (isset($config['allow'][$key])) {
                $allowedConfig[$key] = false;
                foreach ($config['allow'][$key] as $role) {
                    if ($users->hasRole($currentUserId, $role)) {
                        $allowedConfig[$key] = true;
                        break;
                    }
                }
            }
        }

        return $allowedConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig()
    {
        return [
            'templates' => [
                'meta' => '@bolt/_metatags.twig',
            ],
            'eastereggchance'  => 0.075,
            'disableeasteregg' => false,
        ];
    }
}
