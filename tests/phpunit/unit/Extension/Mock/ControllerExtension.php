<?php

namespace Bolt\Tests\Extension\Mock;

use Bolt\Extension\SimpleExtension;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *  Mock extension that extends SimpleExtension for testing the ControllerExtensionTrait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ControllerExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function registerFrontendRoutes(ControllerCollection $collection)
    {
        $collection->match('foobar', [$this, 'fooBar']);
    }

    /**
     * {@inheritdoc}
     */
    protected function registerBackendRoutes(ControllerCollection $collection)
    {
        $collection->match('foobar', [$this, 'fooBar']);
    }

    public function fooBar(Request $request)
    {
        $str = sprintf('Requested URL was %s', $request->getBaseUrl());

        return new Response($str, Response::HTTP_I_AM_A_TEAPOT);
    }
}
