<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Translation\Translator as Trans,
    Symfony\Component\Translation\Loader as TranslationLoader;
use Bolt\Application;
use Bolt\BaseExtension;

require_once('Seo.php');
require_once('src/SEO.php');

class Extension extends BaseExtension
{

    private $version = "v0.9.2";

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->app['config']->getFields()->addField(new SEOField());
    }

    public function initialize() {

        $end = $this->app['config']->getWhichEnd();

        if ($end =='backend') {
            
            $this->app->before(array($this, 'before'));

            $this->app['htmlsnippets'] = true;

            $this->addCss('assets/seo.css');
            // $this->addJavascript('assets/seo.js', true);
        }

        $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig");

        $currentUser    = $this->app['users']->getCurrentUser();
        $currentUserId  = $currentUser['id'];
        
        $this->config['allowed'] = array();
        foreach ($this->config['allow'] as $key => $field) {
            $this->config["allowed"][$key] = false;
            foreach ($this->config['allow'][$key] as $role) {
                if ($this->app['users']->hasRole($currentUserId, $role)) {
                    $this->config["allowed"][$key] = true;
                    break;
                }
            }
        } 

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

    public function getName()
    {
        return "seo";
    }

}
