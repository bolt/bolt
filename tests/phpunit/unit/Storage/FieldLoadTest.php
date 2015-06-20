<?php
namespace Bolt\Tests\Storage;

use Bolt\Storage;
use Bolt\Storage\EntityManager;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Mocks\LoripsumMock;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Storage/Repository and field transforms for load and hydrate
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FieldLoadTest extends BoltUnitTest
{
    public function testRelationsLoad()
    {
        $this->resetDb();
        $app = $this->getApp();
        $this->addNewUser($app, 'admin', 'Admin', 'admin');
        $app['schema']->repairTables();
        $this->addSomeContent();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $record = $repo->find(1);

        foreach ($record->entries as $entry) {
            $this->assertNotEmpty($entry->id);
            $this->assertNotEmpty($entry->slug);
        }
    }

    public function testTaxonomyLoad()
    {
        $app = $this->getApp();
        $app['schema']->repairTables();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $record = $repo->find(1);
        $this->assertTrue(is_array($record->categories));
        $this->assertTrue(is_array($record->tags));
    }

    protected function addSomeContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $app['config']->set('taxonomy/categories/options', ['news']);
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(['showcases', 'entries', 'pages']);

        // We also set some relations between showcases and entries
        $showcases = $storage->getContent('showcases');
        $randEntries = $storage->getContent('entries/random/2');
        foreach ($showcases as $show) {
            foreach ($randEntries as $key => $entry) {
                $show->setRelation('entries', $key);
                $storage->saveContent($show);
            }
        }
    }
}
