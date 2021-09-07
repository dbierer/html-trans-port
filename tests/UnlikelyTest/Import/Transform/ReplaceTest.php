<?php
namespace WP_CLI\UnlikelyTest\Import\Transform;

use WP_CLI\Unlikely\Import\Transform\{Replace,TransformInterface};
use PHPUnit\Framework\TestCase;
class ReplaceTest extends TestCase
{
    public $replace = NULL;
    public function testImplementsTransformInterface()
    {
        $expected = TRUE;
        $replace = new Replace();
        $actual = ($replace instanceof TransformInterface);
        $this->assertEquals($expected, $actual, 'Class does not implement TransformInterface');
    }
    public function testInvokeRemovesText()
    {
        $text = '<img src="https://my.web.site.com/images/test" />';
        $expected = '<img src="/images/test" />';
        $params = ['search' => 'https://my.web.site.com', 'replace' => '', 'case-sensitive' => FALSE];
        $actual = (new Replace())($text, $params);
        $this->assertEquals($expected, $actual, 'Text was not replaced.');
    }
    public function testInvokeCaseInSensitiveWorks()
    {
        $text = '<img src="https://my.web.site.com/images/test" />';
        $expected = '<img src="/images/test" />';
        $params = ['search' => 'HTTPS://my.web.site.com', 'replace' => '', 'case-sensitive' => FALSE];
        $actual = (new Replace())($text, $params);
        $this->assertEquals($expected, $actual, 'Replacement was not case-insensitive');
    }
    public function testInvokeCaseSensitiveWorks()
    {
        $text = '<img src="https://my.web.site.com/images/test" />';
        $expected = '<img src="https://my.web.site.com/images/test" />';
        $params = ['search' => 'HTTPS://my.web.site.com', 'replace' => '', 'case-sensitive' => TRUE];
        $actual = (new Replace())($text, $params);
        $this->assertEquals($expected, $actual, 'Replacement was not case-sensitive');
    }
}
