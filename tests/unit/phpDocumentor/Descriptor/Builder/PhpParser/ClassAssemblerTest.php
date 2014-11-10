<?php
/**
 * phpDocumentor
 *
 * PHP Version 5.3
 *
 * @copyright 2010-2014 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Descriptor\Builder\PhpParser;

use Mockery as m;
use phpDocumentor\Descriptor\ProjectDescriptorBuilder;
use phpDocumentor\Reflection\DocBlock;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;

class ClassAssemblerTest extends \PHPUnit_Framework_TestCase
{
    const EXAMPLE_NAME      = 'Class_';
    const EXAMPLE_NAMESPACE = 'My\Space';
    const EXAMPLE_LINE      = 10;
    const EXAMPLE_DOCBLOCK  = <<<DOCBLOCK
/**
 * This is a Summary.
 * This is a description
 * @package PackageName
 * @author Mike van Riel
 */
DOCBLOCK;
    const EXAMPLE_SUMMARY = 'This is a Summary.';
    const EXAMPLE_DESCRIPTION = 'This is a description';
    const EXAMPLE_PARENT = 'My\Space\SubName\ParentClass';
    const EXAMPLE_IMPLEMENTS1 = 'My\Space\SubName\ParentInterface1';
    const EXAMPLE_IMPLEMENTS2 = 'My\Space\SubName\ParentInterface2';
    const EXAMPLE_PACKAGE_NAME = 'PackageName';

    /** @var ClassAssembler */
    private $fixture;

    /** @var ProjectDescriptorBuilder|m\MockInterface */
    private $builderMock;

    /**
     * Creates the fixture and its dependencies.
     */
    protected function setUp()
    {
        $this->builderMock = m::mock('phpDocumentor\Descriptor\ProjectDescriptorBuilder');

        $this->fixture = new ClassAssembler();
        $this->fixture->setBuilder($this->builderMock);
    }

    /**
     * @covers phpDocumentor\Descriptor\Builder\PhpParser\ClassAssembler::create
     * @covers phpDocumentor\Descriptor\Builder\PhpParser\ClassAssembler::extractNamespace
     */
    public function testAssembleAClassDescriptor()
    {
        $docBlock = $this->givenADocBlock();

        $class = $this->givenAnExampleClassNode($docBlock);

        $descriptor = $this->fixture->create($class);

        $fqsen = '\\' . self::EXAMPLE_NAMESPACE . '\\' . self::EXAMPLE_NAME;
        $this->assertSame(self::EXAMPLE_NAME, $descriptor->getName());
//        $this->assertSame(self::EXAMPLE_PACKAGE_NAME, $descriptor->getPackage());
        $this->assertSame('\\' . self::EXAMPLE_NAMESPACE, $descriptor->getNamespace());
        $this->assertSame($fqsen, $descriptor->getFullyQualifiedStructuralElementName());
        $this->assertSame(self::EXAMPLE_LINE, $descriptor->getLine());
        $this->assertSame('\\' . self::EXAMPLE_PARENT, $descriptor->getParent());
        $this->assertSame(
            array(
                '\\' . self::EXAMPLE_IMPLEMENTS1 => '\\' . self::EXAMPLE_IMPLEMENTS1,
                '\\' . self::EXAMPLE_IMPLEMENTS2 => '\\' . self::EXAMPLE_IMPLEMENTS2,
            ),
            $descriptor->getInterfaces()->getAll()
        );
    }

    /**
     * @covers phpDocumentor\Descriptor\Builder\PhpParser\ClassAssembler::create
     * @covers phpDocumentor\Descriptor\Builder\PhpParser\ClassAssembler::extractNamespace
     */
    public function DocBlockIsProperlyExtractedWhenAssemblingAClassDescriptor()
    {
        list($docBlock, $varTagMock) = $this->givenADocBlock();
        $Class = $this->givenAnExampleClassNode($docBlock);

        $descriptor = $this->fixture->create($Class);

        $this->assertSame(self::EXAMPLE_SUMMARY, $descriptor->getSummary());
        $this->assertSame(self::EXAMPLE_DESCRIPTION, $descriptor->getDescription());
        $this->assertCount(1, $descriptor->getTags()->getAll());
        $this->assertCount(1, $descriptor->getTags()->get('var')->getAll());
        $this->assertSame($varTagMock, $descriptor->getTags()->get('var')->get(0));
    }

    /**
     * Creates and returns a new Class Node as is generated by PHP-Parser.
     *
     * @param DocBlock $docBlock
     *
     * @return Class_
     */
    private function givenAnExampleClassNode(DocBlock $docBlock)
    {
        $class = new Class_(
            self::EXAMPLE_NAME,
            array(
                'extends' => new Name(self::EXAMPLE_PARENT),
                'implements' => array(
                    new Name(self::EXAMPLE_IMPLEMENTS1),
                    new Name(self::EXAMPLE_IMPLEMENTS2)
                ),
            )
        );
        $class->namespacedName = new Name(self::EXAMPLE_NAMESPACE . '\\' . self::EXAMPLE_NAME);
        $class->setLine(self::EXAMPLE_LINE);
        $class->docBlock = $docBlock;

        return $class;
    }

    /**
     * Returns a DocBlock with a mocked @var tag.
     *
     * @return array A DocBlock object and a mocked VarTag object
     */
    private function givenADocBlock()
    {
        $docBlock = new DocBlock(self::EXAMPLE_DOCBLOCK);

        $authorTagMock = m::mock('phpDocumentor\Descriptor\Tag\AuthorDescriptor');
        $packageTagMock = m::mock('phpDocumentor\Descriptor\Tag');
        $packageTagMock->shouldReceive('getDescription')->andReturn(self::EXAMPLE_PACKAGE_NAME);

        $this->builderMock->shouldReceive('buildDescriptor')
            ->once()
            ->with(m::type('phpDocumentor\Reflection\DocBlock\Tag'))
            ->andReturn($packageTagMock);
        $this->builderMock->shouldReceive('buildDescriptor')
            ->with(m::type('phpDocumentor\Reflection\DocBlock\Tag\AuthorTag'))
            ->andReturn($authorTagMock);

        return $docBlock;
    }
}