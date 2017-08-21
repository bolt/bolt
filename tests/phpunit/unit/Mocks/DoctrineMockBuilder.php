<?php

namespace Bolt\Tests\Mocks;

use Doctrine\DBAL;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Mock Builder for Doctrine objects.
 */
class DoctrineMockBuilder extends TestCase
{
    /**
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform|MockObject
     */
    public function getDatabasePlatformMock()
    {
        $mock = $this->getAbstractMock(
            DBAL\Platforms\AbstractPlatform::class,
            [
                'getName',
                'getReservedKeywordsClass',
            ]
        );

        $mock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('mysql'));

        $mock->expects($this->any())
            ->method('getReservedKeywordsClass')
            ->will($this->returnValue(DBAL\Platforms\Keywords\MySQLKeywords::class));

        return $mock;
    }

    /**
     * @return \Doctrine\DBAL\Connection|MockObject
     */
    public function getConnectionMock()
    {
        $mock = $this->getMockBuilder(DBAL\Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'beginTransaction',
                    'commit',
                    'rollback',
                    'prepare',
                    'query',
                    'executeQuery',
                    'executeUpdate',
                    'getDatabasePlatform',
                    'createQueryBuilder',
                    'connect',
                    'insert',
                ]
            )
            ->getMock();

        $mock->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($this->getStatementMock()));

        $mock->expects($this->any())
            ->method('query')
            ->will($this->returnValue($this->getStatementMock()));

        $mock->expects($this->any())
            ->method('executeQuery')
            ->will($this->returnValue($this->getStatementMock()));

        $mock->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($this->getQueryBuilderMock($mock)));

        $mock->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($this->getDatabasePlatformMock()));

        return $mock;
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     *
     * @return QueryBuilder|MockObject
     */
    public function getQueryBuilderMock($connection)
    {
        $exprMock = $this->getMockBuilder(ExpressionBuilder::class)
            ->setConstructorArgs([$connection])
            ->getMock()
        ;
        $mock = $this->getMockBuilder(QueryBuilder::class)
            ->setConstructorArgs([$connection])
            ->setMethods(['expr'])
            ->getMock()
        ;
        $mock->expects($this->any())
            ->method('expr')
            ->will($this->returnValue($exprMock));

        return $mock;
    }

    /**
     * @param mixed $returnValue
     *
     * @return \Doctrine\DBAL\Driver\Statement|MockObject
     */
    public function getStatementMock($returnValue = 1)
    {
        $mock = $this->getAbstractMock(
            DoctrineDbalStatementInterface::class,
            [
                'bindValue',
                'execute',
                'rowCount',
                'fetchColumn',
            ]
        );

        $mock->expects($this->any())
            ->method('fetchColumn')
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @param string $class   The class name
     * @param array  $methods The available methods
     *
     * @return MockObject
     */
    protected function getAbstractMock($class, array $methods)
    {
        return $this->getMockForAbstractClass(
            $class,
            [],
            '',
            true,
            true,
            true,
            $methods,
            false
        );
    }
}
