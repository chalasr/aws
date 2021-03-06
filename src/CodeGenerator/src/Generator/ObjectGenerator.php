<?php

declare(strict_types=1);

namespace AsyncAws\CodeGenerator\Generator;

use AsyncAws\CodeGenerator\Definition\ListShape;
use AsyncAws\CodeGenerator\Definition\MapShape;
use AsyncAws\CodeGenerator\Definition\StructureShape;
use AsyncAws\CodeGenerator\File\FileWriter;
use AsyncAws\CodeGenerator\Generator\CodeGenerator\TypeGenerator;
use AsyncAws\CodeGenerator\Generator\Naming\ClassName;
use AsyncAws\CodeGenerator\Generator\Naming\NamespaceRegistry;
use AsyncAws\Core\StreamableBodyInterface;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

/**
 * Generate API client methods and result classes.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Jérémy Derussé <jeremy@derusse.com>
 *
 * @internal
 */
class ObjectGenerator
{
    use ValidableTrait;

    /**
     * @var ClassName[]
     */
    private $generated = [];

    /**
     * @var NamespaceRegistry
     */
    private $namespaceRegistry;

    /**
     * @var FileWriter
     */
    private $fileWriter;

    /**
     * @var TypeGenerator
     */
    private $typeGenerator;

    /**
     * @var EnumGenerator
     */
    private $enumGenerator;

    public function __construct(NamespaceRegistry $namespaceRegistry, FileWriter $fileWriter, ?TypeGenerator $typeGenerator = null, ?EnumGenerator $enumGenerator = null)
    {
        $this->namespaceRegistry = $namespaceRegistry;
        $this->fileWriter = $fileWriter;
        $this->typeGenerator = $typeGenerator ?? new TypeGenerator($this->namespaceRegistry);
        $this->enumGenerator = $enumGenerator ?? new EnumGenerator($this->namespaceRegistry, $fileWriter);
    }

    public function generate(StructureShape $shape): ClassName
    {
        if (isset($this->generated[$shape->getName()])) {
            return $this->generated[$shape->getName()];
        }

        $this->generated[$shape->getName()] = $className = $this->namespaceRegistry->getObject($shape);

        $namespace = new PhpNamespace($className->getNamespace());
        $class = $namespace->addClass($className->getName());

        // Named constructor
        $this->namedConstructor($shape, $class);
        $this->addProperties($shape, $class, $namespace);
        $this->generateValidate($shape, $class, $namespace);

        $this->fileWriter->write($namespace);

        return $className;
    }

    private function namedConstructor(StructureShape $shape, ClassType $class): void
    {
        $class->addMethod('create')
            ->setStatic(true)
            ->setReturnType('self')
            ->setBody('return $input instanceof self ? $input : new self($input);')
            ->addParameter('input');

        // We need a constructor
        $constructor = $class->addMethod('__construct');
        $constructor->addComment($this->typeGenerator->generateDocblock($shape, $this->generated[$shape->getName()], false, false, true));
        $constructor->addParameter('input')->setType('array');

        $constructorBody = '';
        foreach ($shape->getMembers() as $member) {
            $memberShape = $member->getShape();
            if ($memberShape instanceof StructureShape) {
                $objectClass = $this->generate($memberShape);
                $constructorBody .= strtr('$this->NAME = isset($input["NAME"]) ? CLASS::create($input["NAME"]) : null;' . "\n", ['NAME' => $member->getName(), 'CLASS' => $objectClass->getName()]);
            } elseif ($memberShape instanceof ListShape) {
                $listMemberShape = $memberShape->getMember()->getShape();

                // Check if this is a list of objects
                if ($listMemberShape instanceof StructureShape) {
                    $objectClass = $this->generate($listMemberShape);
                    $constructorBody .= strtr('$this->NAME = array_map(function($item) { return CLASS::create($item); }, $input["NAME"] ?? []);' . "\n", ['NAME' => $member->getName(), 'CLASS' => $objectClass->getName()]);
                } else {
                    $constructorBody .= strtr('$this->NAME = $input["NAME"] ?? [];' . "\n", ['NAME' => $member->getName()]);
                }
            } elseif ($memberShape instanceof MapShape) {
                $mapValueShape = $memberShape->getValue()->getShape();

                if ($mapValueShape instanceof StructureShape) {
                    $objectClass = $this->generate($mapValueShape);
                    $constructorBody .= strtr('$this->NAME = array_map(function($item) { return CLASS::create($item); }, $input["NAME"] ?? []);' . "\n", ['NAME' => $member->getName(), 'CLASS' => $objectClass->getName()]);
                } else {
                    $constructorBody .= strtr('$this->NAME = $input["NAME"] ?? [];' . "\n", ['NAME' => $member->getName()]);
                }
            } else {
                $constructorBody .= strtr('$this->NAME = $input["NAME"] ?? null;' . "\n", ['NAME' => $member->getName()]);
            }
        }
        $constructor->setBody($constructorBody);
    }

    /**
     * Add properties and getters.
     */
    private function addProperties(StructureShape $shape, ClassType $class, PhpNamespace $namespace): void
    {
        foreach ($shape->getMembers() as $member) {
            $nullable = $returnType = null;
            $memberShape = $member->getShape();
            $property = $class->addProperty($member->getName())->setPrivate();
            if (null !== $propertyDocumentation = $memberShape->getDocumentation()) {
                $property->setComment(GeneratorHelper::parseDocumentation($propertyDocumentation));
            }

            [$returnType, $parameterType, $memberClassName] = $this->typeGenerator->getPhpType($memberShape);

            if (!empty($memberShape->getEnum())) {
                $enumClassName = $this->enumGenerator->generate($memberShape);
                $namespace->addUse($enumClassName->getFqdn());
            }

            if ($memberShape instanceof StructureShape) {
                $this->generate($memberShape);
            } elseif ($memberShape instanceof MapShape) {
                $mapKeyShape = $memberShape->getKey()->getShape();
                if ('string' !== $mapKeyShape->getType()) {
                    throw new \RuntimeException('Complex maps are not supported');
                }

                if (($valueShape = $memberShape->getValue()->getShape()) instanceof StructureShape) {
                    $this->generate($valueShape);
                }
                if (!empty($valueShape->getEnum())) {
                    $enumClassName = $this->enumGenerator->generate($valueShape);
                    $namespace->addUse($enumClassName->getFqdn());
                }

                $nullable = false;
            } elseif ($memberShape instanceof ListShape) {
                $memberShape->getMember()->getShape();

                if (($memberShape = $memberShape->getMember()->getShape()) instanceof StructureShape) {
                    $this->generate($memberShape);
                }
                if (!empty($memberShape->getEnum())) {
                    $enumClassName = $this->enumGenerator->generate($memberShape);
                    $namespace->addUse($enumClassName->getFqdn());
                }

                $nullable = false;
            } elseif ($member->isStreaming()) {
                $returnType = StreamableBodyInterface::class;
                $parameterType = StreamableBodyInterface::class;
                $namespace->addUse(StreamableBodyInterface::class);
                $nullable = false;
            }

            $method = $class->addMethod('get' . $member->getName())
                ->setReturnType($returnType)
                ->setBody(strtr('
                    return $this->NAME;
                ', [
                    'NAME' => $member->getName(),
                ]));

            $nullable = $nullable ?? !$member->isRequired();
            if ($parameterType && $parameterType !== $returnType && (null === $memberClassName || $memberClassName->getName() !== $parameterType)) {
                $method->addComment('@return ' . $parameterType . ($nullable ? '|null' : ''));
            }
            $method->setReturnNullable($nullable);
        }
    }
}
