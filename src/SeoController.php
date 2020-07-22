<?php

namespace Bolt\Extension\BobdenOtter\Seo;

use Bolt\Application;
use Bolt\Controller\Base;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SeoController
 *
 * @package Bolt\Extension\BobdenOtter\Seo
 */
class SeoController extends Base
{
    /**
     * @param Application $app
     * @param Request $request
     * @return mixed|RedirectResponse
     */
    public function callbackShortlinkPageId(Application $app, Request $request)
    {
        $pageId = $request->get('p');

        if (!$pageId) {
            return $this->handleShortUrlMatch($app, $request, null, $pageId);
        }

        $match = $this->findShortUrlMatch($app, '?p=' . $pageId);

        return $this->handleShortUrlMatch($app, $request, $match, $pageId);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param $shortlink
     * @return mixed|RedirectResponse
     */
    public function callbackShortlink(Application $app, Request $request, $shortlink)
    {
        $match = $this->findShortUrlMatch($app, $shortlink);

        return $this->handleShortUrlMatch($app, $request, $match, $shortlink);
    }

    /**
     * @param Application $app
     * @param string $query
     * @return mixed
     */
    private function findShortUrlMatch(Application $app, $query)
    {
        $repository   = $app['storage']->getRepository('pages');
        $queryBuilder = $repository->createQueryBuilder();

        $match = $queryBuilder
            ->where($queryBuilder->expr()->like('seo', ':shortlink'))
            ->setMaxResults(1)
            ->setParameter('shortlink', '%"shortlink":"/' . $query . '"%')
            ->execute()
            ->fetch()
        ;

        return $match;
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param $match
     * @param null $pageId
     * @return RedirectResponse
     */
    private function handleShortUrlMatch(Application $app, Request $request, $match, $pageId = null)
    {
        if ($match) {
            return new RedirectResponse('/' . $match['slug'], Response::HTTP_FOUND);
        }

        $controller = $app['controller.frontend'];

        return $controller->record($request, 'pages', $pageId);
    }

    /**
     * @param ControllerCollection $collection
     */
    public function addRoutes(ControllerCollection $collection)
    {
    }
}