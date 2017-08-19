<?php

namespace Bolt\Tests\Composer;

use Bolt\Common\Json;
use Bolt\Composer\Package;
use Composer\Package\CompletePackage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Composer\Package
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PackageTest extends TestCase
{
    private $input = [
        'type'        => 'bolt/extension',
        'name'        => 'bolt/test',
        'description' => 'An extension for Bolt.',
        'authors'     => [
            ['name' => 'Bolt Contributor'],
            ['name' => 'Bolt Core Member'],
        ],
        'version'     => 'composer',
        'keywords'    => ['test', 'unit'],
        'require'     => ['bolt/bolt' => '1.2.3'],
    ];

    public function testCreateFromComposerPackage()
    {
        $package = new CompletePackage($this->input['name'], '2.4.5.0', '2.5.0');
        $result = Package::createFromComposerPackage($package);

        $this->assertInstanceOf(Package::class, $result);
    }

    public function testCreateFromComposerJson()
    {
        $result = Package::createFromComposerJson($this->input);

        $this->assertInstanceOf(Package::class, $result);
    }

    public function testJsonSerialize()
    {
        $expected = '{"status":null,"type":"bolt/extension","name":"bolt/test","title":null,"description":"An extension for Bolt.","version":"composer","authors":[{"name":"Bolt Contributor"},{"name":"Bolt Core Member"}],"keywords":["test","unit"],"readmeLink":null,"configLink":null,"repositoryLink":null,"constraint":"1.2.3","valid":false,"enabled":false}';
        $result = Package::createFromComposerJson($this->input);

        $this->assertSame($expected, Json::dump($result));
        $this->assertSame($this->input['name'], $result->getName());
    }

    public function testSetters()
    {
        $expected = '{"status":"installed","type":"bolt/extension","name":"bolt/test","title":"bolt/test","description":"An extension for Bolt.","version":"composer","authors":[{"name":"Bolt Contributor"},{"name":"Bolt Core Member"}],"keywords":["test","unit"],"readmeLink":"README.md","configLink":"README.md","repositoryLink":"GitHub","constraint":"^1.0","valid":true,"enabled":false}';
        $result = Package::createFromComposerJson(['type' => null, 'name' => null]);
        $result->setStatus('installed');
        $result->setType($this->input['type']);
        $result->setName($this->input['name']);
        $result->setTitle($this->input['name']);
        $result->setDescription($this->input['description']);
        $result->setVersion($this->input['version']);
        $result->setAuthors($this->input['authors']);
        $result->setKeywords($this->input['keywords']);
        $result->setReadmeLink('README.md');
        $result->setConfigLink('README.md');
        $result->setRepositoryLink('GitHub');
        $result->setConstraint('^1.0');
        $result->setValid(true);
        $result->setEnabled(false);

        $this->assertSame($expected, Json::dump($result));
    }
}
