<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha\tests;

use PHPUnit\Framework\TestCase;
use Rucaptcha\ConfigurableTrait;

class ConfigurableTraitTest extends TestCase
{
    /**
     * @expectedException \Rucaptcha\Exception\InvalidArgumentException
     */
    public function testSetOptionThrowsExceptionWithoutIgnoreFlag()
    {
        $mock = $this->buildConfigurableClass();
        $mock->setOptions(['incorrectName' => 100], false);
    }

    public function testSetOptionDoNotThrowsExceptionWithIgnoreFlag()
    {
        $mock = $this->buildConfigurableClass();
        $mock->setOptions(['incorrectName' => 100], true);
    }


    public function testUseSetterIfExist()
    {
        $mock = $this->buildConfigurableClass();
        $mock->setOptions(['withSetterProperty' => 2]);
        $this->assertEquals($mock->withSetterProperty, 3);  // Setter logic: 2 + 1
    }

    public function testInitPublicProperty()
    {
        $mock = $this->buildConfigurableClass();
        $mock->setOptions(['withoutSetterProperty' => 2]);
        $this->assertEquals($mock->withoutSetterProperty, 2);
    }


    protected function buildConfigurableClass()
    {
        return new MockClassForTestingConfigurableTrait();
    }
}

class MockClassForTestingConfigurableTrait
{
    use ConfigurableTrait;

    public $withoutSetterProperty;
    public $withSetterProperty;

    public function setWithSetterProperty($value)
    {
        $this->withSetterProperty = $value + 1;
    }
}