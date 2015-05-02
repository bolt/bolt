<?php

namespace Bolt\Tests\Mocks;

/**
 * Mock Builder for Doctrine objects
 */
class DoctrineMockBuilder extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getDatabasePlatformMock()
    {
        $mock = $this->getAbstractMock(
            'Doctrine\DBAL\Platforms\AbstractPlatform',
            array(
                'getName'
            )
        );

        $mock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('mysql'));

        return $mock;
    }

    /**
     * @return \Doctrine\DBAL\Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getConnectionMock()
    {
        $mock = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(
                array(
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
                    'insert'
                )
            )
            ->getMock();

        $mock->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($this->getStatementMock()));

        $mock->expects($this->any())
            ->method('query')
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
     * @return Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryBuilderMock($connection)
    {
        $exprmock = $this->getMock('Doctrine\DBAL\Query\Expression\ExpressionBuilder', null, array($connection));
        $mock = $this->getMock("Doctrine\DBAL\Query\QueryBuilder", array('expr'), array($connection));
        $mock->expects($this->any())
            ->method('expr')
            ->will($this->returnValue($exprmock));

        return $mock;
    }

    /**
     * @return \Doctrine\DBAL\Driver\Statement|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getStatementMock()
    {
        $mock = $this->getAbstractMock(
            'Bolt\Tests\Mocks\DoctrineDbalStatementInterface', // In case you run PHPUnit <= 3.7, use 'Mocks\DoctrineDbalStatementInterface' instead.
            array(
                'bindValue',
                'execute',
                'rowCount',
                'fetchColumn',
            )
        );

        $mock->expects($this->any())
            ->method('fetchColumn')
            ->will($this->returnValue(1));

        return $mock;
    }

    /**
     * @param string $class   The class name
     * @param array  $methods The available methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAbstractMock($class, array $methods)
    {
        return $this->getMockForAbstractClass(
            $class,
            array(),
            '',
            true,
            true,
            true,
            $methods,
            false
        );
    }
}
