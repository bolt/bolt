<?php
namespace Bolt\Tests\Mocks;
use Doctrine\DBAL\Driver\Statement;

/**
* Doctrine DBAL Statement implementing \Iterator.
*
* This class has been created because of a bug in PHPUnit Mock Objects.
*
* @see https://github.com/sebastianbergmann/phpunit-mock-objects/issues/103
*/
interface DoctrineDbalStatementInterface extends \Iterator, Statement
{
} 