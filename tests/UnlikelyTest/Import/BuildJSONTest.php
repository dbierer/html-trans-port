<?php
namespace WP_CLI\UnlikelyTest\Import;

use DateTime;
use DateTimeZone;
use Throwable;
use UnexpectedValueException;
use XmlWriter;
use SimpleXMLElement;
use WP_CLI\Unlikely\Import\{BuildBase, BuildJSON,Extract,BuildInterface};
use PHPUnit\Framework\TestCase;
class BuildJSONTest extends TestCase
{
    public $config = [];
    public $extract = NULL;
    public $build = NULL;
    public $mock_callback = NULL;
    public function setUp() : void
    {
        $this->config = include __DIR__ . '/../../../src/config/config.php';
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $this->extract = new Extract($fn, $this->config);
        $this->build   = new BuildJSON($this->config, $this->extract);
        $this->mock_callback = new class () extends DateTime implements BuildInterface {
            public $build = NULL;
            public function setBuildInstance(BuildBase $build)
            {
                $this->build = $build;
            }
        };
    }
    public function testConstructPostConfigKeyFound()
    {
        $expected = FALSE;
        $actual   = (empty($this->build->post));
        $this->assertEquals($expected, $actual, 'Config key "post" not found');
    }
    public function testBuildJsonAddsScalarItem()
    {
        $post = ['test' => 'TEST'];
        $expected = $post;
        $actual = $this->build->buildJSON('', $post);
        $this->assertEquals($expected, $actual, 'Scalar item not added');
    }
    public function testBuildJsonAddsArrayItem()
    {
        $post = ['test' => ['TEST']];
        $expected = ['test' => ['TEST']];
        $actual = $this->build->buildJSON('', $post);
        $this->assertEquals($expected, $actual, 'Array item not added');
    }
    public function testBuildJsonAddsArrayItemWithCallback()
    {
        $callback = new class () implements BuildInterface {
            public function encode($value) { return strtoupper($value); }
            public function setBuildInstance(BuildBase $build) { /* do nothing */ }
        };
        $key = str_replace("\0", '', get_class($callback));
        $this->config[$key] = ['test'];
        error_log(__METHOD__ . ':' . var_export($this->config, TRUE));
        $this->extract = new Extract(__FILE__, $this->config);
        $this->build   = new BuildJSON($this->config, $this->extract);
        $post =  [
            'test' => [
                'callback' => [
                    'class' => get_class($callback),
                    'method' => 'encode',
                    'args' => 'test'
                ]
            ]
        ];
        $expected = ['test' => 'TEST'];
        $actual = $this->build->buildJSON('', $post);
        $this->assertEquals($expected, $actual, 'Callback did not run');
    }
}
