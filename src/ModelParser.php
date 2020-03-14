<?php

namespace MarkKremer\InfoAboutModels;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use MarkKremer\InfoAboutModels\ModelHelpers\DefaultsModelHelper;
use MarkKremer\InfoAboutModels\ModelHelpers\ModelHelper;
use MarkKremer\InfoAboutModels\Relations\Relation;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;

class ModelParser
{
    use MakesEvaluator;

    /**
     * @var NodeFinder
     */
    private $nodeFinder;

    /**
     * @var ConstExprEvaluator
     */
    private $evaluator;

    /**
     * @var ModelHelper
     */
    private $modelHelper;

    public function __construct()
    {
        $this->nodeFinder = new NodeFinder();
        $this->evaluator = $this->makeEvaluator();
        $this->modelHelper = new DefaultsModelHelper();
    }

    /**
     * @param string $className
     *
     * @return ModelInfo
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function parseClass(string $className): ModelInfo
    {
        $model = $this->parseClassAsPartOfConcreteClass($className, $className);

        // Set defaults
        if ($model->table === null) {
            $model->table = $this->modelHelper->getTable($className);
        }

        return $model;
    }

    /**
     * @param string $className
     * @param string $concreteClassName
     *
     * @return ModelInfo
     * @throws ReflectionException
     * @throws Exception
     */
    private function parseClassAsPartOfConcreteClass(string $className, string $concreteClassName): ModelInfo
    {
        $reflectionClass = new ReflectionClass($className);

        if (! $reflectionClass->isSubclassOf(Model::class) && ! $reflectionClass->isTrait()) {
            throw new Exception("Class $className is not a Laravel model.");
        }

        $classAst = $this->getClassAst($className);
        // TODO: null check

        $model = new ModelInfo();
        $model->name = $reflectionClass->getShortName();
        $model->class = $reflectionClass->getName();
        $model->table = $this->parsePropertyDefault($classAst, 'table');
        $model->relations = $this->parseRelations($classAst, $concreteClassName);

        $traits = $reflectionClass->getTraits();
        foreach ($traits as $trait) {
            $traitModel = $this->parseClassAsPartOfConcreteClass($trait->getName(), $concreteClassName);
            $model->relations = $model->relations->merge($traitModel->relations)->unique('name');
        }

        $parentClass = $reflectionClass->getParentClass();
        if ($parentClass !== false && $parentClass->name !== Model::class) {
            $parentModel = $this->parseClassAsPartOfConcreteClass($parentClass->getName(), $concreteClassName);
            $model->table = $model->table ?: $parentModel->table;
            $model->relations = $model->relations->merge($parentModel->relations)->unique('name');
        }

        return $model;
    }

    /**
     * @param string $className
     *
     * @return ClassLike|null
     * @throws ReflectionException
     */
    private function getClassAst(string $className): ?ClassLike
    {
        $file = $this->getFileThatContainsClass($className);

        $ast = $this->parseFile($file);

        return $this->findClassInAst($className, $ast);
    }

    /**
     * @param string $className
     *
     * @return string
     * @throws ReflectionException
     */
    private function getFileThatContainsClass(string $className): string
    {
        $reflection = new ReflectionClass($className);

        return $reflection->getFileName();
    }

    /**
     * @param string $file
     *
     * @return Node[]
     */
    private function parseFile(string $file): array
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        $ast = $parser->parse(file_get_contents($file));

        return $this->resolveNames($ast);
    }

    /**
     * @param Node[] $ast
     *
     * @return Node[]
     */
    private function resolveNames($ast): array
    {
        $nameResolver = new NameResolver();
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($nameResolver);

        return $nodeTraverser->traverse($ast);
    }

    /**
     * @param string $className
     * @param Node[] $ast
     *
     * @return ClassLike
     */
    private function findClassInAst(string $className, $ast): ?ClassLike
    {
        $classes = $this->nodeFinder->findInstanceOf($ast, ClassLike::class);

        return collect($classes)->first(function (ClassLike $class) use ($className): bool {
            return $class->namespacedName->toString() === $className;
        });
    }

    /**
     * @param ClassLike $class
     * @param string    $propertyName
     *
     * @return mixed|null
     */
    private function parsePropertyDefault(ClassLike $class, string $propertyName)
    {
        $properties = $this->nodeFinder->findInstanceOf($class, PropertyProperty::class);

        $property = collect($properties)->first(function (PropertyProperty $property) use ($propertyName) {
            return $property->name->name === $propertyName;
        });

        return $this->evaluateProperty($property);
    }

    /**
     * @param PropertyProperty|null $property
     *
     * @return mixed|null
     */
    private function evaluateProperty(?PropertyProperty $property)
    {
        if ($property === null) {
            return null;
        }

        try {
            return $this->evaluator->evaluateSilently($property->default);
        } catch (ConstExprEvaluationException $e) {
            return null;
        }
    }

    private function parseRelations(ClassLike $class, string $concreteClassName): Collection
    {
        $methods = $this->nodeFinder->findInstanceOf($class, ClassMethod::class);

        return $this->parseRelationsFromMethods($concreteClassName, $methods);
    }

    /**
     * @param string        $concreteClassName
     * @param ClassMethod[] $methods
     *
     * @return Collection
     */
    private function parseRelationsFromMethods(string $concreteClassName, array $methods): Collection
    {
        return collect($methods)->map(function (ClassMethod $method) use ($concreteClassName): ?Relation {
            return $this->parseRelationMethod($concreteClassName, $method);
        })->filter();
    }

    /**
     * @param string      $concreteClassName
     * @param ClassMethod $method
     *
     * @return Relation|null
     * @throws Exception
     */
    private function parseRelationMethod(string $concreteClassName, ClassMethod $method): ?Relation
    {
        return (new RelationMethodParser($concreteClassName, $method))->getRelation();
    }
}
