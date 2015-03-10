<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Application;
use Bolt\BaseExtension;
use Bolt\Helpers\Html;

require_once('Seo.php');

class Extension extends BaseExtension
{


    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->app['config']->getFields()->addField(new GridField());
        if ($this->app['config']->getWhichEnd()=='backend') {
            $this->app['htmlsnippets'] = true;
        }
        $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig");

    }

    public function initialize() {
        if ($this->app['config']->getWhichEnd()=='backend') {
            $this->addCss('assets/seo.css');
            // $this->addJavascript('assets/seo.js', true);
        }

        $this->app['twig']->addGlobal('seoconfig', $this->config);

        $this->addTwigFunction('seo', 'seo', array('is_safe' => array('html')));

    }

    public function getName()
    {
        return "seo";
    }

    public function seo($what, $record = null)
    {

        // Make sure $record contains something sensible.
        if (empty($record)) {
            if (empty($this->record)) {
                $vars = $this->app['twig']->getGlobals();
                $record = $this->record = $vars['record'];
            } else {
                $record = $this->record;
            }
        }

        // dump($record->contenttype);

        // Find the seofield and the fallback fields for description and title
        $seofieldname = "";
        $titlefield = "";
        $descriptionfield = "";

        foreach($record->contenttype['fields'] as $fieldname => $field) {
            if ($field['type'] == "seo") {
                $seofieldname = $fieldname;
            }
            if (($titlefield == "") && in_array($fieldname, $this->config['fields']['title']) ) {
                $titlefield = $fieldname;
            }
            if (($descriptionfield == "") && in_array($fieldname, $this->config['fields']['description']) ) {
                $descriptionfield = $fieldname;
            }
        }

        if (empty($seofieldname)) {
            return "<!-- no SEO field found. -->";
        }

        $seovalues = json_decode($record->values[$seofieldname]);

        switch($what) {
            case 'title':
                return $this->seoTitle($record, $seovalues, $titlefield);
                break;
            case 'description':
                return $this->seoDescription($record, $seovalues, $descriptionfield);
                break;
            case 'metatags':
                return $this->seoMetaTags($record, $seovalues, $titlefield, $descriptionfield);
                break;                
        }

        return '';

    }

    private function seoTitle($record, $seovalues, $titlefield)
    {

        $postfix = sprintf(" %s %s",
            !empty($this->config['title_separator']) ? $this->config['title_separator'] : "|",
            !empty($this->config['title_postfix']) ? $this->config['title_postfix'] : $this->app['config']->get('general/sitename')
        );

        if (!empty($seovalues->title)) {
            $title = $seovalues->title . $postfix;
        } else {
            $title = $record->values[$titlefield] . $postfix;
        }

        // Note: Do not trim the length. Longer lengths are not beneficial for how they are
        // shown in google, but they _are_ indexed.
        return $title;

    }


    private function seoDescription($record, $seovalues, $descriptionfield)
    {

        if (!empty($seovalues->description)) {
            $description = $seovalues->description;
        } else {
            $description = $record->values[$descriptionfield];
        }

        return Html::trimText(strip_tags($description), $this->config['description_length']);

    }

    private function seoMetaTags($record, $seovalues, $titlefield, $descriptionfield)
    {

        $image = $this->findImage($record);

        $vars = array(
            'title' => $this->seoTitle($record, $seovalues, $titlefield),
            'description' => $this->seoDescription($record, $seovalues, $descriptionfield),
            'image' => $this->findImage($record)
        );

        $html = $this->app['render']->render('_metatags.twig', $vars);

        return new \Twig_Markup($html, 'UTF-8');

    }


    private function findImage($record) 
    {

        foreach($record->contenttype['fields'] as $fieldname => $field) {
            if ($field['type'] == "image") {
                $image = $record->values[$fieldname]['file'];
                break;
            }
        }

        if (!empty($image)) {
            $image = sprintf('%s%s%s',
                $this->app['paths']['canonical'],
                $this->app['paths']['files'],
                $image
            );

            return $image; 

        } else {

            return '';

        }


    }


}






