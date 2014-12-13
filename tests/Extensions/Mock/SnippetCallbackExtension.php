<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Extensions\ExtensionInterface;
use Bolt\Application;
use Bolt\Extensions\Snippets\Location as SnippetLocation;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class SnippetCallbackExtension extends Extension
{

    public function getSnippets()
    {
        return array(array(SnippetLocation::START_OF_HEAD, 'snippetCallBack' ));
    }
    
    public function getName()
    {
        return "snippetcallback";
    }

    public function snippetCallBack()
    {
        return '<meta name="test-snippet" />';
    }
    
    public function parseSnippet($callback, $var1 = "", $var2 = "", $var3 = "")
    {
        return call_user_func(array($this, $callback), $var1, $var2, $var3);
    }
    
    public function parseWidget($callback, $var1 = "", $var2 = "", $var3 = "")
    {
        return call_user_func(array($this, $callback), $var1, $var2, $var3);
    }
   
}
