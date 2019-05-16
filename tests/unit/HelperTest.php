<?php


class HelperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testUpperize()
    {
		$this->assertEquals('FILE_UPLOAD', \Jodit\Helper::Upperize('fileUpload'));
		$this->assertEquals('FILE_UPLOAD', \Jodit\Helper::Upperize('FileUpload'));
		$this->assertEquals('FIL_EUPLOAD', \Jodit\Helper::Upperize('FilEUpload'));
		$this->assertEquals('FILE', \Jodit\Helper::Upperize('File'));
    }

	public function testCamelCase() {
		$this->assertEquals('FileUpload', \Jodit\Helper::CamelCase('FILE_UPLOAD'));
		$this->assertEquals('File', \Jodit\Helper::CamelCase('FILE'));
	}
	public function testNormalizePath() {
		$this->assertEquals('C:/sdfsdf/', \Jodit\Helper::NormalizePath('C:\\sdfsdf\\'));
		$this->assertEquals('C:/sdfsdf/', \Jodit\Helper::NormalizePath('C:/sdfsdf/'));
		$this->assertEquals('C:/sdfsdf/', \Jodit\Helper::NormalizePath('C://\\sdfsdf/'));
	}

	public function testArray_get() {
        $this->assertEquals('bar', \Jodit\Helper::array_get(['foo' => 'bar'], 'foo'));
        $this->assertEquals(false, \Jodit\Helper::array_get([], 'foo', false));
        $this->assertEquals(null, \Jodit\Helper::array_get([], 'foo'));
    }
}