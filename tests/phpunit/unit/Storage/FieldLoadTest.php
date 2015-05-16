<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\EntityManager;
use Bolt\Storage\ContentRepository;
use Bolt\Entity\Content;
use Bolt\Tests\Mocks\LoripsumMock;
use Bolt\Storage;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Storage/Repository and field transforms
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FieldLoadTest extends BoltUnitTest
{
    
    
    public function testCreateAndHydrate()
    {
        $app = $this->getApp();
        $this->addNewUser($app, 'admin', 'Admin', 'admin');;
        $app['integritychecker']->repairTables();
        $this->addSomeContent();
        $em = new EntityManager($app['db'], $app['dispatcher'], $app['storage.metadata']);
        $repo = $em->getRepository('showcases');
        
        $record = $repo->find(1);
                                
        foreach ($record->entries as $entry) {
            $this->assertNotEmpty($entry->id);
            $this->assertNotEmpty($entry->slug);
        }        

        
    }
    
    protected function addSomeContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $app['config']->set('taxonomy/categories/options', array('news'));
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(array('showcases', 'entries', 'pages'));
        
        // We also set some relations between showcases and entries
        $showcases = $storage->getContent("showcases");
        $randEntries = $storage->getContent("entries/random/2");
        foreach ($showcases as $show) {
            foreach ($randEntries as $key=>$entry) {
                $show->setRelation('entries', $key);
                $storage->saveContent($show);
            }
            
        }
        
    }
    
    

    
}
