<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Helpers\Html;


class SEO
{

   public function __construct(\Silex\Application $app, $config, $version)
    {
        $this->app = $app;
        $this->config = $config;
        $this->version = $version;

        $this->initialize();

    }

    public function initialize($record = null)
    {

        // Make sure $record contains something sensible.
        if (empty($record)) {
            if (empty($this->record)) {
                // See if we can get it from the global twig variables
                $vars = $this->app['twig']->getGlobals();
                if (isset($vars['record'])) {
                    $record = $this->record = $vars['record'];
                }
            } else {
                $record = $this->record;
            }
        }

        $titlefield = '';
        $descriptionfield = '';
        $this->values = array();

        // Find the seofield and the fallback fields for description and title
        if (!empty($record)) {
            foreach($record->contenttype['fields'] as $fieldname => $field) {
                if ($field['type'] == "seo") {
                    $this->values['record'] = json_decode($record->values[$fieldname], true);
                }
                if (($titlefield == "") && in_array($fieldname, $this->config['fields']['title']) ) {
                    $this->values['inferred']['title'] = $record->values[$fieldname];
                }
                if (($descriptionfield == "") && in_array($fieldname, $this->config['fields']['description']) ) {
                    $this->values['inferred']['description'] = $record->values[$fieldname];
                }
            }
        }

        // If we need these, no record is set, we're _not_ on the homepage (or it's not set)
        // In this case we fall back to the defaults set in our config, or the global
        if (!empty($this->config['default']['title'])) {
            $this->values['default']['title'] = $this->config['default']['title'];
        } else {
            $this->values['default']['title'] = $this->app['config']->get('general/sitename');
        }
        if (!empty($this->config['default']['description'])) {
            $this->values['default']['description'] = $this->config['default']['description'];
        } else {
            $this->values['default']['description'] = $this->app['config']->get('general/payoff');
        }

        if (!empty($seofieldname)) {
            $this->values['record'] = json_decode($record->values[$seofieldname], true);
        }

    }

    public function title($record = null)
    {

        $this->initialize($record);

        $postfix = sprintf(" %s %s",
            !empty($this->config['title_separator']) ? $this->config['title_separator'] : "|",
            !empty($this->config['title_postfix']) ? $this->config['title_postfix'] : $this->app['config']->get('general/sitename')
        );

        if (!empty($this->values['record']['title'])) {
            $title = $this->values['record']['title'] . $postfix;
        } else if (!empty($this->values['inferred']['title'])) {
            $title = $this->values['inferred']['title'] . $postfix;
        } else {
            $title = $this->values['default']['title'] . $postfix;
        }

        // Note: Do not trim the length. Longer lengths are not beneficial for how they are
        // shown in google, but they _are_ indexed.
        return $title;


    }

    public function description($record = null)
    {

        $this->initialize($record);

        if (!empty($this->values['record']['description'])) {
            $description = $this->values['record']['description'];
        } else if (!empty($this->values['inferred']['description'])) {
            $description = $this->values['inferred']['description'];
        } else {
            $description = $this->values['default']['description'];
        }

        return Html::trimText(strip_tags($description), $this->config['description_length']);

    }



    public function metatags($record = null)
    {

        $this->initialize($record);

        $vars = array(
            'title' => $this->title(),
            'description' => $this->description(),
            'image' => $this->findImage(),
            'version' => $this->version
        );

        $html = $this->app['render']->render('_metatags.twig', $vars);

        return new \Twig_Markup($html, 'UTF-8');

    }


    private function findImage()
    {

        foreach($this->record->contenttype['fields'] as $fieldname => $field) {
            if ($field['type'] == "image") {
                if (isset($this->record->values[$fieldname]['file'])) {
                    $image = $this->record->values[$fieldname]['file'];
                } else {
                    $image = $this->record->values[$fieldname];
                }
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

