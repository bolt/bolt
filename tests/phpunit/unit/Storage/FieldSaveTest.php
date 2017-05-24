<?php

namespace Bolt\Tests\Storage;

use Bolt\Legacy\Storage;
use Bolt\Storage\Collection\Taxonomy;
use Bolt\Storage\Entity;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Mocks\LoripsumMock;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Storage/Repository and field transforms for load and hydrate.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FieldSaveTest extends BoltUnitTest
{
    public function testRelationsSave()
    {
        $app = $this->getApp();
        $app['schema']->update();
        $this->addSomeContent();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $record = $repo->find(1);

        foreach ($record->relation['entries'] as $entry) {
            $this->assertNotEmpty($entry->id);
            $this->assertNotEmpty($entry->slug);
        }

        $newRels = $em->createCollection(Entity\Relations::class);
        $record->setRelation($newRels);
        $em->save($record);

        // Test that there are no relations now on a fresh search
        $record1 = $repo->find(1);
        $this->assertEquals(0, count($record1->relation));
    }

    public function testTaxonomySave()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $record = $repo->find(1);

        $this->assertInstanceOf(Taxonomy::class, $record->taxonomy['categories']);
        $this->assertInstanceOf(Taxonomy::class, $record->taxonomy['tags']);

        $taxonomy = $em->createCollection(Entity\Taxonomy::class);
        $taxonomy->setFromPost(['categories' => []], $record);
        $record->setTaxonomy($taxonomy);
        $repo->save($record);

        // Test that there are no relations now on a fresh search
        $record1 = $repo->find(1);
        $this->assertEquals(0, count($record1->taxonomy['categories']));
    }

    public function testEntityCreateTaxonomySave()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $newEntity = $repo->create(['title' => 'Testing', 'slug' => 'testing', 'status' => 'published']);

        $taxonomy = $em->createCollection(Entity\Taxonomy::class);
        $taxonomy->setFromPost(['taxonomy' => ['categories' => ['news', 'events']]], $newEntity);
        $newEntity->setTaxonomy($taxonomy);
        $repo->save($newEntity);

        $savedEntity = $repo->find($newEntity->getId());
        $this->assertEquals(2, count($savedEntity->getCategories()));
    }

    protected function addSomeContent()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $app['request'] = Request::create('/');
        $app['config']->set('taxonomy/categories/options', ['news']);
        $prefillMock = new LoripsumMock();
        $this->setService('prefill', $prefillMock);

        $storage = new Storage($app);
        $storage->prefill(['showcases', 'entries', 'pages']);

        // We also set some relations between showcases and entries
        $showcases = $storage->getContent('showcases');
        $randEntries = $storage->getContent('entries/random/2');
        foreach ($showcases as $show) {
            foreach (array_keys($randEntries) as $key) {
                $show->setRelation('entries', $key);
                $storage->saveContent($show);
            }
        }
    }
}
