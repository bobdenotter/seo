<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Helpers\Html;
use Bolt\Legacy\Content;
use Silex\Application;

class SEO
{
    /** @var bool */
    protected $canonicalSet = false;
    /** @var array */
    protected $config;
    /** @var string */
    protected $version;
    /** @var Content */
    protected $record;
    /** @var array */
    protected $values;

    /**
     * Constructor.
     *
     * @param Application $app
     * @param array       $config
     * @param string      $version
     */
    public function __construct(Application $app, $config, $version)
    {
        $this->app = $app;
        $this->config = $config;
        $this->version = $version;

        $this->record = [];
        $this->values = [];
    }

    /**
     * @param Content $record
     */
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
        } elseif (!empty($vars['contenttype'])) {
            $this->values['inferred']['title'] = $vars['contenttype'];
        } elseif (!empty($vars['taxonomy'])) {
            $this->values['inferred']['title'] = sprintf('%s %s',
                $this->app['translator']->trans('general.phrase.overview-for'),
                $vars['slug']
            );
        }

        $titlefield = '';
        $descriptionfield = '';

        // Find the seofield and the fallback fields for description and title
        if (!empty($record)) {
            foreach ($record->contenttype['fields'] as $fieldname => $field) {
                if ($field['type'] == 'seo') {
                    $this->values['record'] = json_decode($record->values[$fieldname], true);
                }
                if (($titlefield == '') && in_array($fieldname, $this->config['fields']['title'])) {
                    $this->values['inferred']['title'] = $titlefield = $record->values[$fieldname];
                }
                if (($descriptionfield == '') && in_array($fieldname, $this->config['fields']['description'])) {
                    $this->values['inferred']['description'] = $descriptionfield = $record->values[$fieldname];
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

        if (!empty($this->config['default']['keywords'])) {
            $this->values['default']['keywords'] = $this->config['default']['keywords'];
        } else {
            $this->values['default']['keywords'] = '';
        }

        if (!empty($this->config['meta_robots'])) {
            $this->values['default']['meta_robots'] = $this->config['meta_robots'];
        } else {
            $this->values['default']['meta_robots'] = 'index, follow';
        }

        if (!empty($seofieldname)) {
            $this->values['record'] = json_decode($record->values[$seofieldname], true);
        }

        $this->setCanonical();
    }

    /**
     * @param Content $record
     *
     * @return string
     */
    public function title($record = null)
    {
        $this->initialize($record);

        // Set the postfix: nothing, sitename, or as configured.
        if ($this->config['title_postfix'] === false) {
            $postfix = '';
        } else {
            $postfix = sprintf(' %s %s',
                !empty($this->config['title_separator']) ? $this->config['title_separator'] : '|',
                !empty($this->config['title_postfix']) ? $this->config['title_postfix'] : $this->app['config']->get('general/sitename')
            );
        }

        if (!empty($this->values['record']['title'])) {
            $title = $this->values['record']['title'] . $postfix;
        } elseif (!empty($this->values['inferred']['title'])) {
            $title = $this->values['inferred']['title'] . $postfix;
        } else {
            $title = $this->values['default']['title'] . $postfix;
        }

        // Note: Do not trim the length. Longer lengths are not beneficial for how they are
        // shown in google, but they _are_ indexed.
        return $this->cleanUp($title);;
    }

    /**
     * @param Content $record
     *
     * @return string
     */
    public function description($record = null)
    {
        $this->initialize($record);

        if (!empty($this->values['record']['description'])) {
            $description = $this->values['record']['description'];
        } elseif (!empty($this->values['inferred']['description'])) {
            $description = $this->values['inferred']['description'];
        } else {
            $description = $this->values['default']['description'];
        }

        $description = $this->cleanUp($description);

        return Html::trimText($description, $this->config['description_length']);
    }

    /**
     * @param Content $record
     *
     * @return string
     */
    public function keywords($record = null)
    {
        $this->initialize($record);

        if (!empty($this->values['record']['keywords'])) {
            $keywords = $this->values['record']['keywords'];
        } elseif (!empty($this->values['inferred']['keywords'])) {
            $keywords = $this->values['inferred']['keywords'];
        } else {
            $keywords = $this->values['default']['keywords'];
        }

        $keywords = $this->cleanUp($keywords);

        return Html::trimText($keywords, $this->config['keywords_length']);
    }

    /**
     * @param Content $record
     *
     * @return array
     */
    public function robots($record = null)
    {
        $this->initialize($record);

        if (!empty($this->values['record']['robots'])) {
            $robots = $this->values['record']['robots'];
        } elseif (!empty($this->values['inferred']['meta_robots'])) {
            $robots = $this->values['inferred']['meta_robots'];
        } else {
            $robots = $this->values['default']['meta_robots'];
        }

        return $robots;
    }

    /**
     * @param Content $record
     *
     * @return \Twig_Markup
     */
    public function metatags($record = null)
    {
        $this->initialize($record);

        $vars = [
            'title'       => $this->title(),
            'description' => $this->description(),
            'keywords'    => $this->keywords(),
            'image'       => $this->findImage(),
            'version'     => $this->version,
            'robots'      => $this->robots(),
            'canonical'   => $this->app['resources']->getUrl('canonicalurl'),
        ];

        $html = $this->app['twig']->render($this->config['templates']['meta'], $vars);

        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Find a suitable image for the OG image tag.
     *
     * @return array|string
     */
    private function findImage()
    {
        // If there's no record set, there's nothing to be done.
        if (empty($this->record)) {
            return '';
        }

        // First, if we've set a preference for the fields in config, check those
        // fields for a suitable candidate.
        if (isset($this->config['fields']['image']) && !empty($this->config['fields']['image'])) {
            foreach ((array)$this->config['fields']['image'] as $fieldname) {
                if (isset($this->record->contenttype['fields'][$fieldname])) {
                    if ($image = $this->findImageHelper($fieldname)) {
                       break;
                    }
                }
            }
        }

        // Otherwise, iterate over the available fields, until we've found the
        // first image.
        if (empty($image)) {
            foreach ($this->record->contenttype['fields'] as $fieldname => $field) {
                if ($image = $this->findImageHelper($fieldname)) {
                    break;
                }
            }
        }

        if (!empty($image)) {
            return sprintf('%sfiles/%s', $this->app['resources']->getUrl('rooturl'), $image);
        } else {
            return '';
        }
    }

    /**
     * Helper function for findImage()
     *
     * @param string $fieldname
     */
    private function findImageHelper($fieldname)
    {
        $field = $this->record->contenttype['fields'][$fieldname];

        $image = '';

        if ($field['type'] == 'image') {
            if (isset($this->record->values[$fieldname]['file'])) {
                $image = $this->record->values[$fieldname]['file'];
            } else {
                $image = $this->record->values[$fieldname];
            }
        } elseif ($field['type'] == 'imagelist') {
            if (isset($this->record->values[$fieldname][0]['filename'])) {
                $image = $this->record->values[$fieldname][0]['filename'];
            }
        }

        return $image;
    }

    public function setCanonical($canonical = '')
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
            if (strpos($canonical, 'http') !== 0) {
                // Relative link, so we add the domain.
                if (strpos($canonical, '/') !== 0) {
                    $canonical = '/' . $canonical;
                }
                $url = sprintf('%s%s', $paths['canonical'], $canonical);
                $this->app['resources']->setUrl('canonicalurl', $url);
            } else {

                // Absolute link, so we don't add the domain.
                $this->app['resources']->setUrl('canonicalurl', $canonical);
            }
        }
    }

    private function cleanUp($string)
    {
        $string = strip_tags($string);
        $string = str_replace(["\r", "\n"], '', $string);
        $string = preg_replace('/\s+/', ' ', $string);

        return $string;
    }

}
