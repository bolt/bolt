<?php
namespace Bolt\Controllers\Backend;

use Bolt\Controllers\Base;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend controller for logging routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Log extends Base
{
    /**
     * @see \Bolt\Controllers\Base::addControllers()
     *
     * @param ControllerCollection $c
     */
    protected function addControllers(ControllerCollection $c)
    {
        return $c;
    }

    /*
     * Routes
     */

    /**
     * Change log overview route
     *
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionChangeOverview(Request $request)
    {
        $action = $request->query->get('action');

        if ($action == 'clear') {
            $this->app['logger.manager']->clear('change');
            $this->addFlash('success', Trans::__('The change log has been cleared.'));

            return $this->redirectToRoute('changelog');
        } elseif ($action == 'trim') {
            $this->app['logger.manager']->trim('change');
            $this->addFlash('success', Trans::__('The change log has been trimmed.'));

            return $this->redirectToRoute('changelog');
        }

        $activity = $this->app['logger.manager']->getActivity('change', 16);

        return $this->render('activity/changelog.twig', array('entries' => $activity));
    }

    /**
     * System log overview route
     *
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionSystemOverview(Request $request)
    {
        $action = $request->query->get('action');

        if ($action == 'clear') {
            $this->app['logger.manager']->clear('system');
            $this->addFlash('success', Trans::__('The system log has been cleared.'));

            return $this->redirectToRoute('systemlog');
        } elseif ($action == 'trim') {
            $this->app['logger.manager']->trim('system');
            $this->addFlash('success', Trans::__('The system log has been trimmed.'));

            return $this->redirectToRoute('systemlog');
        }

        $level = $request->query->get('level');
        $context = $request->query->get('context');

        $activity = $app['logger.manager']->getActivity('system', 16, $level, $context);

        return $this->render('activity/systemlog.twig', array('entries' => $activity));
    }
}
