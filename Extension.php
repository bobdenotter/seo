<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Application;
use Bolt\BaseExtension;

require_once('Seo.php');

class Extension extends BaseExtension
{
  

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->app['config']->getFields()->addField(new GridField());
        if ($this->app['config']->getWhichEnd()=='backend') {
            $this->app['htmlsnippets'] = true;
            $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig");
        }
    }

    public function initialize() {
        if ($this->app['config']->getWhichEnd()=='backend') {
            $this->addCss('assets/seo.css');
            // $this->addJavascript('assets/handsontable.full.min.js', true);
            // $this->addJavascript('assets/start.js', true);
        }

        // $contenttype = $this->app['config']->get('contenttypes');


        $this->app['twig']->addGlobal('seoconfig', $this->config);

    }

    public function getName()
    {
        return "gridfield";
    }

}






