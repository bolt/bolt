<?php

namespace Bolt\Tests\Extension\Mock;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mock controller.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Controller implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        /** @var $ctr ControllerCollection */
        $ctr = $app['controllers_factory'];

        $ctr->match('foobar', [$this, 'fooBar']);

        return $ctr;
    }

    public function fooBar(Request $request)
    {
        $str = sprintf('Requested URL was %s', $request->getBaseUrl());

        return new Response($str, Response::HTTP_I_AM_A_TEAPOT);
    }
}
