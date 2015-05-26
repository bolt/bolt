<?php
namespace Bolt\Provider;

use Bolt\Form\FormEventTypeExtension;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\DefaultCsrfProvider;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\SessionCsrfProvider;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension as FormValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\ResolvedFormTypeFactory;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

/**
 * Symfony Form component Provider.
 *
 * Based upon Silex\Provider\FormServiceProvider from Silex 1.2 with parts
 * introduced from 2.0. Ideally this will eventually phased out, but Silex and
 * Symfony are currently incompatible with our needs for both in-built
 * subscribers and Session token handling.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class FormServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!class_exists('Locale') && !class_exists('Symfony\Component\Locale\Stub\StubLocale')) {
            throw new \RuntimeException('You must either install the PHP intl extension or the Symfony Locale Component to use the Form extension.');
        }

        if (!class_exists('Locale')) {
            $r = new \ReflectionClass('Symfony\Component\Locale\Stub\StubLocale');
            $path = dirname(dirname($r->getFilename())).'/Resources/stubs';

            require_once $path.'/functions.php';
            require_once $path.'/Collator.php';
            require_once $path.'/IntlDateFormatter.php';
            require_once $path.'/Locale.php';
            require_once $path.'/NumberFormatter.php';
        }

        /*
         * Custom secret. Default Silex uses an MD5 hash of this file name, most
         * likely as it is deemed to be an implementation detail. Well, we're
         * implementing then.
         */
        $app['form.secret'] = $app->share(function ($app) {
            if (!$app['session']->isStarted()) {
                return;
            } elseif ($secret = $app['session']->get('form.secret')) {
                return $secret;
            } else {
                $secret = $app['randomgenerator']->generate(32);
                $app['session']->set('form.secret', $secret);

                return $secret;
            }
        });

        $app['form.types'] = $app->share(function ($app) {
            return [];
        });

        // Add the Bolt event subscriber extension.
        $app['form.type.extensions'] = $app->share(function ($app) {
            return [
                new FormEventTypeExtension($app)
            ];

        });

        $app['form.type.guessers'] = $app->share(function ($app) {
            return [];
        });

        /*
         * Custom CSRF provider set up.
         *
         * Silex 1.2 providers (SessionCsrfProvider and DefaultCsrfProvider) are
         * deprecated.
         */
        $app['form.csrf_provider'] = $app->share(function ($app) {
            $storage = isset($app['session']) ? new SessionTokenStorage($app['session']) : new NativeSessionTokenStorage();

            return new CsrfTokenManager(null, $storage);
        });

        $app['form.extension.csrf'] = $app->share(function ($app) {
            if (isset($app['translator'])) {
                return new CsrfExtension($app['form.csrf_provider'], $app['translator']);
            }

            return new CsrfExtension($app['form.csrf_provider']);
        });

        $app['form.extensions'] = $app->share(function ($app) {
            $extensions = [
                $app['form.extension.csrf'],
                new HttpFoundationExtension(),
            ];

            if (isset($app['validator'])) {
                $extensions[] = new FormValidatorExtension($app['validator']);

                if (isset($app['translator'])) {
                    $r = new \ReflectionClass('Symfony\Component\Form\Form');
                    $file = dirname($r->getFilename()).'/Resources/translations/validators.'.$app['locale'].'.xlf';
                    if (file_exists($file)) {
                        $app['translator']->addResource('xliff', $file, $app['locale'], 'validators');
                    }
                }
            }

            return $extensions;
        });

        $app['form.factory'] = $app->share(function ($app) {
            return Forms::createFormFactoryBuilder()
                ->addExtensions($app['form.extensions'])
                ->addTypes($app['form.types'])
                ->addTypeExtensions($app['form.type.extensions'])
                ->addTypeGuessers($app['form.type.guessers'])
                ->setResolvedTypeFactory($app['form.resolved_type_factory'])
                ->getFormFactory()
            ;
        });

        $app['form.resolved_type_factory'] = $app->share(function ($app) {
            return new ResolvedFormTypeFactory();
        });
    }

    public function boot(Application $app)
    {
    }
}
