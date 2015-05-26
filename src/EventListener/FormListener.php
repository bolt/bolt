<?php
namespace Bolt\EventListener;

use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Symfony Forms listener.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FormListener implements EventSubscriberInterface
{
    /** @var \Silex\Application $app */
    protected $app;

    /**
     * Constructor function.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle the form event at the beginning of the Form::setData() method.
     *
     * @param FormEvent $event
     */
    public function onFormPreSetData(FormEvent $event)
    {
    	if (!$this->app['session']->isStarted()) {
    		// Enable route specific sesion cookies, generally speaking for front end
	        $this->app['session'] = $this->app->share(function () {
		        $storage = new NativeSessionStorage(
		            array(
		                    'name'            => $this->app['token.session.name'],
		                    'cookie_path'     => $this->app['request']->getRequestUri(),
		                    'cookie_domain'   => $this->app['config']->get('general/cookies_domain'),
		                    'cookie_secure'   => $this->app['config']->get('general/enforce_ssl'),
		                    'cookie_httponly' => true,
		            		),
		            $this->app['session.storage.handler']
		        );

		        return new Session($storage);
	        });
    	}
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => ['onFormPreSetData', 10000],
        );
    }
}
