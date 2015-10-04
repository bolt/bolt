<?php

namespace Bolt\Tests\Storage\Query;

use Bolt\Storage\Query\SearchConfig;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/QueryTest.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SearchConfigTest extends BoltUnitTest
{
    public function testSetup()
    {
        $app = $this->getApp();
        $search = new SearchConfig($app['config']);
        $this->assertTrue(is_array($search->getConfig('pages')));
    }

    public function testNonExistent()
    {
        $app = $this->getApp();
        $search = new SearchConfig($app['config']);
        $this->assertFalse($search->getConfig('nonexistent'));
    }

    public function testIndividualField()
    {
        $app = $this->getApp();
        $search = new SearchConfig($app['config']);
        $this->assertTrue(is_array($search->getFieldConfig('pages', 'title')));
        $this->assertFalse($search->getFieldConfig('pages', 'nonexistent'));
    }

    public function testViewless()
    {
        $app = $this->getApp();
        $app['config']->set('contenttypes/pages/viewless', true);
        $search = new SearchConfig($app['config']);
        $this->assertFalse($search->getConfig('pages'));
    }

    public function testWeighting()
    {
        $app = $this->getApp();
        $app['config']->set('contenttypes/pages/fields/body/searchweight', 100);
        $search = new SearchConfig($app['config']);
        $fieldConfig = $search->getFieldConfig('pages', 'body');
        $this->assertEquals(100, $fieldConfig['weight']);
    }

    public function testTaxonomyWeighting()
    {
        $app = $this->getApp();
        $app['config']->set('taxonomy/chapters/searchweight', 100);
        $search = new SearchConfig($app['config']);
        $fieldConfig = $search->getFieldConfig('pages', 'chapters');
        $this->assertEquals(100, $fieldConfig['weight']);
    }

    public function testTagsTaxonomyWeighting()
    {
        $app = $this->getApp();
        $search = new SearchConfig($app['config']);
        $fieldConfig = $search->getFieldConfig('entries', 'tags');
        $this->assertEquals(75, $fieldConfig['weight']);
    }
}
