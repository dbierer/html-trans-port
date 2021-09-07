<?php
namespace WP_CLI\UnlikelyTest\Import;

use DateTime;
use DateTimeZone;
use Throwable;
use UnexpectedValueException;
use InvalidArgumentException;
use XmlWriter;
use SimpleXMLElement;
use WP_CLI\Unlikely\Import\{BuildBase,Extract,BuildInterface};
use PHPUnit\Framework\TestCase;
class BuildBaseTest extends TestCase
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
        $this->build   = new BuildBase($this->config, $this->extract);
        $this->mock_callback = new class () extends DateTime implements BuildInterface {
            public $build = NULL;
            public function setBuildInstance(BuildBase $build)
            {
                $this->build = $build;
            }
        };
    }
    public function testAddCallbackStoresObject()
    {
        $before = count($this->build->callbackManager);
        $this->build->addCallback($this->mock_callback);
        $after = count($this->build->callbackManager);
        $expected = TRUE;
        $actual = ($after > $before);
        $this->assertEquals($expected, $actual, 'CallbackManager did not store object');
    }
    public function testaddCallbackThrowsInvalidArgumentExceptionIfCallbackDoesntImplementBuildInterface()
    {
        try {
            $this->build->addCallback(new DateTime());
            $actual = 'DateTime';
        } catch (Throwable $t) {
            $actual = get_class($t);
        }
        $expected = 'InvalidArgumentException';
        $this->assertEquals($expected, $actual, 'addCallback does not throw InvalidArgumentException');
    }
    public function testGetCallbackReturnsNullIfCallbackDoesntExist()
    {
        $expected = NULL;
        $actual = $this->build->getCallback('ArrayObject');
        $this->assertEquals($expected, $actual, 'getCallback() does not return NULL if callback not registered');
    }
    public function testGetCallbackDoesNotReportEmptyIfCallbackDoesntExist()
    {
        $expected = TRUE;
        $result   = $this->build->getCallback('ArrayObject');
        $actual   = empty($result);
        $this->assertEquals($expected, $actual, 'getCallback() does not report empty if callback not registered');
    }
    public function testBuildBaseStoresExtractInstance()
    {
        $expected = Extract::class;
        $obj = $this->build->getCallback(Extract::class);
        $actual = (empty($obj)) ? NULL : get_class($obj);
        $this->assertEquals($expected, $actual, 'Extract instance not found in callbackManager');
    }
    public function testDoCallbackUsingFunction()
    {
        $params = ['callable' => 'strtoupper', 'args' => 'test'];
        $expected = 'TEST';
        $actual = $this->build->doCallback($params);
        $this->assertEquals($expected, $actual, 'Callable argument does not work');
    }
    public function testDoCallbackUsingAnonFunctionAndArrayArgs()
    {
        $func = function (array $args) {
            $out = [];
            foreach ($args as $obj) $out[] = $obj->format('Y-m-d');
            return $out;
        };
        $params = ['callable' => $func, 'args' => [new DateTime('now'), new DateTime('tomorrow')]];
        $expected = date('Y-m-d');
        $actual = $this->build->doCallback($params)[0] ?? '';
        $this->assertEquals($expected, $actual, 'Anonymous function with array arguments does not work');
    }
    public function testUseCallbackManager()
    {
        $class = get_class($this->mock_callback);
        $this->build->config[$class] = ['now', new DateTimeZone('UTC')];
        $this->build->addCallback($this->mock_callback);
        $params = ['class' => $class, 'method' => 'format', 'args' => 'Y-m-d'];
        $expected = date('Y-m-d');
        $actual = $this->build->useCallbackManager($params);
        $this->assertEquals($expected, $actual, 'useCallbackManager using callback class does not work');
    }
    public function testUseCallbackManagerThrowsBadMethodCallExceptionIfMethodDoesntExist()
    {
        $class = get_class($this->mock_callback);
        $this->build->config[$class] = ['now', new DateTimeZone('UTC')];
        $this->build->addCallback($this->mock_callback);
        $params = ['class' => $class, 'method' => 'xyz'];
        $expected = 'BadMethodCallException';
        try {
            $actual = $this->build->useCallbackManager($params);
        } catch (Throwable   $e) {
            $actual = get_class($e);
        }
        $this->assertEquals($expected, $actual, 'useCallbackManager does not throw BadMethodCallException if method does not exist');
    }
    public function testUseCallbackManagerThrowsExceptionIfNoConfig()
    {
        $class = get_class($this->mock_callback);
        $params = ['class' => $class, 'method' => 'format'];
        $expected = 'Exception';
        try {
            $actual = $this->build->useCallbackManager($params);
        } catch (Throwable   $e) {
            $actual = get_class($e);
        }
        $this->assertEquals($expected, $actual, 'useCallbackManager does not throw Exception if config not found');
    }
    public function testDoCallbackUsingCallbackManager()
    {
        $class = get_class($this->mock_callback);
        $this->build->config[$class] = ['now', new DateTimeZone('UTC')];
        $this->build->addCallback($this->mock_callback);
        $params = ['class' => $class, 'method' => 'format', 'args' => 'Y-m-d'];
        $expected = date('Y-m-d');
        $actual = $this->build->doCallback($params);
        $this->assertEquals($expected, $actual, 'doCallback using callback class does not work');
    }
}
