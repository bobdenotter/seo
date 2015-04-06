<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Helpers\Html;
use Symfony\Component\HttpFoundation\Request;

class SEO
{

    protected $canonicalSet = false;

    public function __construct(\Silex\Application $app, $config, $version)
    {
        $this->app = $app;
        $this->config = $config;
        $this->version = $version;

        $this->record = array();
        $this->values = array();

        // $this->initialize(null, true);

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

        // See if we need to override the route.
        $route = $this->app['request']->get('_route');
        if (isset($this->config['robot_override'][$route])) {
            $this->values['inferred']['meta_robots'] = $this->config['robot_override'][$route];
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

        if (!empty($this->config['meta_robots'])) {
            $this->values['default']['meta_robots'] = $this->config['meta_robots'];
        } else {
            $this->values['default']['meta_robots'] = "index, follow";
        }

        if (!empty($seofieldname)) {
            $this->values['record'] = json_decode($record->values[$seofieldname], true);
        }

        $this->setCanonical();

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


    public function robots($record = null)
    {

        $this->initialize($record);

        if (!empty($this->values['record']['robots'])) {
            $robots = $this->values['record']['robots'];
        } else if (!empty($this->values['inferred']['meta_robots'])) {
            $robots = $this->values['inferred']['meta_robots'];
        } else {
            $robots = $this->values['default']['meta_robots'];
        }

        return $robots;

    }


    public function metatags($record = null)
    {

        $this->initialize($record);

        $vars = array(
            'title' => $this->title(),
            'description' => $this->description(),
            'image' => $this->findImage(),
            'version' => $this->version,
            'robots' => $this->robots(),
            'canonical' => $this->app['resources']->getUrl('canonicalurl')
        );

        $html = $this->app['render']->render('_metatags.twig', $vars);

        return new \Twig_Markup($html, 'UTF-8');

    }


    private function findImage()
    {

        if (empty($this->record)) {
            return '';
        }

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

    public function setCanonical($canonical = "")
    {

        // If we set it explicitly, don't "reset" it by default.
        if (empty($canonical) && $this->canonicalSet) {
            return;
        }
        $this->canonicalSet = true;

        $paths = $this->app['resources']->getPaths();

        if (empty($canonical) && !empty($this->values['record']['canonical'])) {
            $canonical = $this->values['record']['canonical'];
        }

        if (!empty($canonical)) {

            if (strpos($canonical, "http") !== 0) {

                // Relative link, so we add the domain.
                if (strpos($canonical, "/") !== 0) {
                    $canonical = "/" . $canonical;
                }
                $url = sprintf("%s%s", $paths['canonical'], $canonical);
                $this->app['resources']->setUrl('canonicalurl', $url);

            } else {

                // Absoloute link, so we don't add the domain.
                $this->app['resources']->setUrl('canonicalurl', $canonical);
            }
        }

    }

}

