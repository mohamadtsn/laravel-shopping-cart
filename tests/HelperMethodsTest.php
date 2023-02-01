<?php


use PHPUnit\Framework\TestCase;

class HelperMethodsTest extends TestCase
{
    public $tempArray;
    public $tempMultiArray;
    public $config;
    public $configOtherFormat;
    public $configNormalFormat;

    protected function setUp(): void
    {
        $this->tempArray = [
            'id' => 456,
            'name' => 'Sample Item 1',
            'price' => 67.99,
            'quantity' => 1,
            'attributes' => [],
        ];
        $this->tempMultiArray = [
            [
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 1,
                'attributes' => [],
            ],
        ];
        $this->config = require(__DIR__ . '/helpers/configMock.php');
        $this->configOtherFormat = require(__DIR__ . '/helpers/configMockOtherFormat.php');
        $this->configNormalFormat = require(__DIR__ . '/helpers/configMockNormalFormat.php');
    }

    public function test_isMultiArray_method_is_false()
    {
        $this->assertFalse(isMultiArray($this->tempArray), 'this array is not multi-dimensional');
    }

    public function test_isMultiArray_method_is_true()
    {
        $this->assertTrue(isMultiArray($this->tempMultiArray), 'this array is multi-dimensional');
    }

    public function test_formatValue_method_enabled_formatting()
    {
        $this->assertEquals('150.000,000', formatValue(150000, true, $this->configOtherFormat), 'not valid formatter');
    }

    public function test_formatValue_method_enabled_formatting_whithout_decimals()
    {
        $this->config['decimals'] = 0;
        $this->assertEquals('150,000', formatValue(150000, true, $this->configNormalFormat), 'not valid formatter');
    }

    public function test_formatValue_method_enabled_formatting_whithout_decimals_with_config()
    {
        $this->assertEquals('150,000', formatValue(150000, true, $this->configNormalFormat), 'not valid formatter');
    }

    public function test_formatValue_method_invalid_number()
    {
        $this->expectError();
        formatValue('1$50,0$00', true, $this->configNormalFormat);
    }
}
