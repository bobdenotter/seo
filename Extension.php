<?php

namespace Bolt\Extension\BobdenOtter\GridField;

use Bolt\Application;
use Bolt\BaseExtension;

require_once('GridField.php');

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
        $this->addCss('assets/handsontable.full.min.css');
        $this->addJavascript('assets/handsontable.full.min.js', true);
        $this->addJavascript('assets/start.js', true);
    }

    public function getName()
    {
        return "gridfield";
    }

}






