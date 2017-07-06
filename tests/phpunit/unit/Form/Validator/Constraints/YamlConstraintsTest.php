<?php

namespace Bolt\Tests\Form\Validator\Constraints;

use Bolt\Form\Validator\Constraints\Yaml;
use Bolt\Form\Validator\Constraints\YamlValidator;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

/**
 * @covers \Bolt\Form\Validator\Constraints\YamlValidator
 * @covers \Bolt\Form\Validator\Constraints\Yaml
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class YamlConstraintsTest extends TestCase
{
    public function testValid()
    {
        $constraint = new Yaml();
        $validator = $this->getValidator();
        $data = 'valid: true';

        $result = $validator->validate($data, $constraint);
        $this->assertFalse($result);
    }

    public function providerNotYamlString()
    {
        return [
            ['valid false'],
            ["Welcome to Koala Country\nDrop Bear Alert High"],
            ["a\nb\nc"],
            [['valid' => false]],
        ];
    }

    /**
     * @dataProvider providerNotYamlString
     */
    public function testNotYamlString($data)
    {
        $constraint = new Yaml();
        $validator = $this->getValidator();

        $result = $validator->validate($data, $constraint);
        $this->assertTrue($result);
    }

    public function testInValidYaml()
    {
        $constraint = new Yaml();
        $validator = $this->getValidator();
        $data = 'valid: [false';

        $result = $validator->validate($data, $constraint);
        $this->assertTrue($result);
    }

    /**
     * @return YamlValidator
     */
    private function getValidator()
    {
        $validator = new YamlValidator();
        $mockValidator = $this->getMockBuilder(ConstraintViolationBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['setParameter', 'addViolation'])
            ->getMock()
        ;
        $mockValidator->method('setParameter')
            ->willReturn($mockValidator)
        ;
        /** @var ExecutionContext|MockObject $mockContext */
        $mockContext = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['buildViolation'])
            ->getMock()
        ;
        $mockContext->method('buildViolation')
            ->willReturn($mockValidator)
        ;
        $validator->initialize($mockContext);

        return $validator;
    }
}
