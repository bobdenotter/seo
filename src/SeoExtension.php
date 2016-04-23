<?php

namespace Bolt\Extension\BobdenOtter\Seo;

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
        return [
            'seoconfig' => 'seoConfig',
        ];
    }

    public function SeoConfig()
    {
        return $this->getConfig();
    }


    protected function registerAssets()
    {
        $asset = new Stylesheet();
        $asset->setFileName('seo.css')
            ->setZone(Zone::BACKEND)
        ;

        return [
            $asset,
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

        echo "joe!";
        die();
        $this->app['twig']->addGlobal('seoconfig', $this->config);

        if ($end == 'frontend') {
            $seo = new SEO($this->app, $this->config, $this->version);
            $this->app['twig']->addGlobal('seo', $seo);
        }

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
