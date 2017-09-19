<?php

namespace Bolt\Tests\Configuration;

use Bolt\Config;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Silex\Application;

/**
 * @covers \Bolt\Config
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ConfigTest extends TestCase
{
    public function providerParseTaxonomyName()
    {
        return [
            'Valid name' => [
                'Kenny Koala', 'name', ['test' => ['slug' => null, 'name' => 'Kenny Koala']],
            ],
            'Missing name' => [
                'Kenny K Koala', 'name', ['test' => ['slug' => 'kenny-k_koala']],
            ],
            'Valid singular name' => [
                'Bruce Koala', 'singular_name', ['test' => ['slug' => null, 'singular_name' => 'Bruce Koala']],
            ],
            'Missing singular_name from slug' => [
                'Kenny K Koala', 'singular_name', ['test' => ['slug' => 'kenny-k_koala']],
            ],
            'Missing singular_name from singular_slug' => [
                'Bruce M Koala', 'singular_name', ['test' => ['slug' => null, 'singular_slug' => 'bruce-m_koala']],
            ],
            'Valid slug' => [
                'kenny-koala', 'slug', ['test' => ['slug' => 'kenny-koala', 'name' => 'Drop Bears', 'singular_name' => 'Drop Bear']],
            ],
            'Missing slug from name' => [ // Should be key in v4
                'drop-bears', 'slug', ['test' => ['name' => 'Drop Bears', 'singular_name' => 'Drop Bear']],
            ],
            'Set true has_sortorder' => [
                true, 'has_sortorder', ['test' => ['slug' => null, 'has_sortorder' => true]],
            ],
            'Set false has_sortorder' => [
                false, 'has_sortorder', ['test' => ['slug' => null, 'has_sortorder' => false]],
            ],
            'Missing has_sortorder' => [
                false, 'has_sortorder', ['test' => ['slug' => null]],
            ],
            'Set true allow_spaces' => [
                true, 'allow_spaces', ['test' => ['slug' => null, 'allow_spaces' => true]],
            ],
            'Set false allow_spaces' => [
                false, 'allow_spaces', ['test' => ['slug' => null, 'allow_spaces' => false]],
            ],
            'Missing allow_spaces' => [
                false, 'allow_spaces', ['test' => ['slug' => null]],
            ],
            'Valid behaves_like' => [
                'gum-leaves', 'behaves_like', ['test' => ['slug' => null, 'behaves_like' => 'gum-leaves']],
            ],
            'Missing behaves_like' => [
                'tags', 'behaves_like', ['test' => ['slug' => null]],
            ],
            'Set true tagcloud' => [
                true, 'tagcloud', ['test' => ['slug' => null, 'tagcloud' => true]],
            ],
            'Set false tagcloud' => [
                false, 'tagcloud', ['test' => ['slug' => null, 'tagcloud' => false]],
            ],
            'Set tagcloud implicitly upon behaves_like === tags' => [
                true, 'tagcloud', ['test' => ['slug' => null, 'behaves_like' => 'tags']],
            ],
            'Do not set tagcloud implicitly upon behaves_like !== tags' => [
                false, 'tagcloud', ['test' => ['slug' => null, 'behaves_like' => 'gum-leaves']],
            ],
            'Indexed options' => [
                ['a-a-a' => 'a a a', 'b-b-b' => 'b b b', 'c-c-c' => 'c c c'],
                'options',
                ['test' => ['slug' => null, 'options' => ['a a a', 'b b b', 'c c c']]],
            ],
            'Associative options' => [
                ['a' => 'aaa', 'bbb' => 'bbb', 'c-c-c' => 'c c c'],
                'options',
                ['test' => ['slug' => null, 'options' => ['a' => 'aaa', 'bbb', 'c c c']]],
            ],
        ];
    }

    /**
     * @dataProvider providerParseTaxonomyName
     *
     * @param mixed  $expected
     * @param string $key
     * @param array  $data
     */
    public function testParseTaxonomyName($expected, $key, array $data)
    {
        $app = new Application();
        $config = new Config($app);
        $rm = new ReflectionMethod(Config::class, 'parseTaxonomy');
        $rm->setAccessible(true);

        $result = $rm->invokeArgs($config, [$data]);

        $this->assertSame($expected, $result['test'][$key]);
    }
}
