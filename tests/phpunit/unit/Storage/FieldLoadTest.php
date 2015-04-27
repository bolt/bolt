<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\EntityManager;
use Bolt\Storage\ContentRepository;
use Bolt\Entity\Content;
use Bolt\Tests\Mocks\LoripsumMock;
use Bolt\Storage;

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
        $app['integritychecker']->repairTables();
        $this->addSomeContent();
        $em = new EntityManager($app['db'], $app['dispatcher'], $app['storage.metadata']);
        $repo = $em->getRepository('showcases');
        
        $record = $repo->find(1);
        
        print_r($record);
    }
    
    protected function addSomeContent()
    {
        $app = $this->getApp();
        $app['config']->set('taxonomy/categories/options', array('news'));
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(array('showcases'));
    }
    
    

    
}
