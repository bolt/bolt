<?php

namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Configuration\PathResolver;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \Bolt\Provider\TranslationServiceProvider
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TranslationServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $this->assertNotNull($app['translator']->getLocale());
    }

    public function testLocaleChange()
    {
        $app = $this->getApp();
        $app['locale'] = 'de_XX';
        $this->assertEquals('de_XX', $app['translator']->getLocale());
    }

    public function testDefaultTranslationLoading()
    {
        $app = $this->makeApp();
        $this->registerTranslationServiceWithCachingDisabled($app);
        $app->initialize();
        $app->boot();
        $this->assertEquals('About', $app['translator']->trans('general.about'));
    }

    public function testShortLocaleFallback()
    {
        $app = $this->makeApp();
        $this->initializeFakeTranslationFiles('xx', 'general.about: "So very about"', $app['path_resolver']);
        $this->registerTranslationServiceWithCachingDisabled($app);
        $app->initialize();
        $app['locale'] = 'xx_XX';
        $app->boot();
        $this->assertEquals('So very about', $app['translator']->trans('general.about'));
    }

    public function testTranslationLoadingOverride()
    {
        $app = $this->makeApp();
        $this->initializeFakeTranslationFiles('en_GB', 'general.about: "Not so about"', $app['path_resolver']);
        $this->registerTranslationServiceWithCachingDisabled($app);
        $app->initialize();
        $app->boot();
        $this->assertEquals('Not so about', $app['translator']->trans('general.about'));
    }

    /**
     * It is mandatory to disable translation caching, otherwise it creates
     * difficulties with regard to testing in isolation.
     *
     * For example, without disabling translation caching, `testDefaultTranslationLoading()`
     * passes, but the following tests fail, as the cached messages are loaded
     * instead of re-building the whole message catalogue.
     *
     * @param Application $app
     */
    protected function registerTranslationServiceWithCachingDisabled(Application $app)
    {
        $app['config']->set('general/caching/translations', false);
    }

    /**
     * @param string       $locale
     * @param string       $fileContent
     * @param PathResolver $pathResolver
     */
    protected function initializeFakeTranslationFiles($locale, $fileContent, PathResolver $pathResolver)
    {
        $fakeAppRoot = $pathResolver->resolve('cache');
        $pathResolver->define('root', $fakeAppRoot);
        $fakeTranslationDir = "{$fakeAppRoot}/app/resources/translations/{$locale}";
        (new Filesystem())->mkdir($fakeTranslationDir);
        file_put_contents("{$fakeTranslationDir}/messages.{$locale}.yml", $fileContent);
    }
}
