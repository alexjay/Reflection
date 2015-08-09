<?php
/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2010-2015 Mike van Riel<mike@phpdoc.org>
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Reflection;

use Mockery as m;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\Php\ProjectFactory;
use phpDocumentor\Reflection\Types\Object_;

/**
 * Intergration tests to check the correct working of processing a file into a project.
 *
 * @coversNothing
 */
class ProjectCreationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProjectFactory
     */
    private $fixture;

    protected function setUp()
    {
        $this->fixture = ProjectFactory::createInstance();
    }

    public function testCreateProjectWithFunctions()
    {
        $fileName = __DIR__ . '/project/simpleFunction.php';

        $project = $this->fixture->create('MyProject',[
            $fileName
        ]);

        $this->assertArrayHasKey($fileName, $project->getFiles());
        $this->assertArrayHasKey('\simpleFunction()', $project->getFiles()[$fileName]->getFunctions());
    }

    public function testCreateProjectWithClass()
    {
        $fileName = __DIR__ . '/project/Pizza.php';
        $project = $this->fixture->create('MyProject', [
            $fileName
        ]);

        $this->assertArrayHasKey($fileName, $project->getFiles());
        $this->assertArrayHasKey('\\Pizza', $project->getFiles()[$fileName]->getClasses());
        $this->assertArrayHasKey('\\Pizza::PACKAGING', $project->getFiles()[$fileName]->getClasses()['\\Pizza']->getConstants());
    }

    public function testWithNamespacedClass()
    {
        $fileName = __DIR__ . '/project/Luigi/Pizza.php';
        $project = $this->fixture->create('MyProject', [
            $fileName
        ]);

        $this->assertArrayHasKey($fileName, $project->getFiles());
        $this->assertArrayHasKey('\\Luigi\\Pizza', $project->getFiles()[$fileName]->getClasses());
        $this->assertEquals('\Pizza', $project->getFiles()[$fileName]->getClasses()['\\Luigi\\Pizza']->getParent());
        $this->assertArrayHasKey(
            '\\Luigi\\Pizza::$instance',
            $project->getFiles()[$fileName]->getClasses()['\\Luigi\\Pizza']->getProperties()
        );

        $methods = $project->getFiles()[$fileName]->getClasses()['\\Luigi\\Pizza']->getMethods();
        $this->assertArrayHasKey(
            '\\Luigi\\Pizza::__construct()',
            $methods
        );

        $this->assertEquals('style', $methods['\\Luigi\\Pizza::__construct()']->getArguments()[0]->getName());
    }

    public function testDocblockOfMethodIsProcessed()
    {
        $fileName = __DIR__ . '/project/Luigi/Pizza.php';
        $project = $this->fixture->create('MyProject', [
            $fileName
        ]);

        $this->assertArrayHasKey($fileName, $project->getFiles());

        $methods = $project->getFiles()[$fileName]->getClasses()['\\Luigi\\Pizza']->getMethods();

        $createInstanceMethod = $methods['\\Luigi\\Pizza::createInstance()'];

        $this->assertInstanceOf(DocBlock::class, $createInstanceMethod->getDocblock());

        $docblock = $createInstanceMethod->getDocblock();
        /** @var Param[] $params */
        $params = $docblock->getTagsByName('param');

        /** @var Object_ $objectType */
        $objectType = $params[0]->getType();

        $this->assertEquals(new Fqsen('\Luigi\Pizza\Style'), $objectType->getFqsen());
    }

    public function testWithUsedParent()
    {
        $fileName = __DIR__ . '/project/Luigi/StyleFactory.php';
        $project = $this->fixture->create('MyProject', [
            $fileName
        ]);

        $this->assertArrayHasKey($fileName, $project->getFiles());
        $this->assertArrayHasKey('\\Luigi\\StyleFactory', $project->getFiles()[$fileName]->getClasses());
        $this->assertEquals('\\Luigi\\Pizza\\PizzaComponentFactory', $project->getFiles()[$fileName]->getClasses()['\\Luigi\\StyleFactory']->getParent());
    }

    public function testWithInterface()
    {
        $fileName = __DIR__ . '/project/Luigi/Valued.php';
        $project = $this->fixture->create('MyProject', [
            $fileName
        ]);

        $this->assertArrayHasKey('\\Luigi\\Valued', $project->getFiles()[$fileName]->getInterfaces());
    }

    public function testWithTrait()
    {
        $fileName = __DIR__ . '/project/Luigi/ExampleNestedTrait.php';
        $project = $this->fixture->create('MyProject', [
            $fileName
        ]);

        $this->assertArrayHasKey('\\Luigi\\ExampleNestedTrait', $project->getFiles()[$fileName]->getTraits());
    }
}
