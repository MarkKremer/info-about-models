<?php

namespace MarkKremer\InfoAboutModels;

use Exception;
use Illuminate\Support\Str;
use MarkKremer\InfoAboutModels\ModelHelpers\DefaultsModelHelper;
use MarkKremer\InfoAboutModels\ModelHelpers\ModelHelper;
use MarkKremer\InfoAboutModels\Relations\BelongsTo as BelongsToRelation;
use MarkKremer\InfoAboutModels\Relations\BelongsToMany as BelongsToManyRelation;
use MarkKremer\InfoAboutModels\Relations\HasMany as HasManyRelation;
use MarkKremer\InfoAboutModels\Relations\HasManyThrough as HasManyThroughRelation;
use MarkKremer\InfoAboutModels\Relations\HasOne as HasOneRelation;
use MarkKremer\InfoAboutModels\Relations\HasOneThrough as HasOneThroughRelation;
use MarkKremer\InfoAboutModels\Relations\Relation;
use MarkKremer\InfoAboutModels\Relations\UnsupportedRelation;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class RelationMethodParser
{
    use MakesEvaluator;

    private $eloquentHelperMethods = [
        'hasOne'         => true,
        'hasMany'        => true,
        'hasOneThrough'  => true,
        'hasManyThrough' => true,
        'belongsTo'      => true,
        'belongsToMany'  => true,
        'morphTo'        => true,
        'morphToMany'    => true,
        'morphedByMany'  => true,
        'morphOne'       => true,
        'morphMany'      => true,
    ];

    /**
     * @var string
     */
    private $concreteClassName;

    /**
     * @var ClassMethod
     */
    private $method;

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

    /**
     * @var Relation|HasOneRelation|HasManyRelation|HasOneThroughRelation|HasManyThroughRelation|BelongsToRelation|BelongsToManyRelation
     */
    private $relation;

    /**
     * @var bool
     */
    private $isRelation = false;

    /**
     * RelationMethodParser constructor.
     *
     * @param string      $concreteClassName
     * @param ClassMethod $classMethod
     *
     * @throws Exception
     */
    public function __construct(string $concreteClassName, ClassMethod $classMethod)
    {
        $this->concreteClassName = $concreteClassName;
        $this->method = $classMethod;
        $this->nodeFinder = new NodeFinder();
        $this->evaluator = $this->makeEvaluator();
        $this->modelHelper = new DefaultsModelHelper();
        $this->relation = new Relation();

        $this->parseName();
        $this->parseArguments();
        $this->parseImplementation();
    }

    public function getRelation(): ?Relation
    {
        return $this->isRelation ? $this->relation : null;
    }

    private function parseName(): void
    {
        $this->relation->name = $this->method->name->name;
    }

    private function parseArguments(): void
    {
        if (count($this->method->getParams()) > 0) {
            $this->relation->hasUnknownsOrLogic = true;
        }
    }

    /**
     * @throws Exception
     */
    private function parseImplementation(): void
    {
        $helper = $this->findEloquentMethodCall();

        if ($helper === null) {
            $this->relation->hasUnknownsOrLogic = true;

            return;
        }

        if (! $this->methodImmediatelyReturnsExpression($helper)) {
            $this->relation->hasUnknownsOrLogic = true;
        }

        $this->isRelation = true;

        $this->parseEloquentMethodCall($helper);
    }

    private function findEloquentMethodCall(): ?MethodCall
    {
        $methodCalls = $this->nodeFinder->findInstanceOf($this->method, MethodCall::class);

        $eloquentMethodCalls = collect($methodCalls)->filter(function (MethodCall $methodCall): bool {
            return $this->isEloquentMethodCall($methodCall);
        });

        if ($eloquentMethodCalls->count() !== 1) {
            return null;
        }

        return $eloquentMethodCalls->first();
    }

    private function isEloquentMethodCall(MethodCall $methodCall): bool
    {
        return $methodCall->var instanceof Variable
            && $methodCall->var->name === 'this'
            && $methodCall->name instanceof Identifier
            && array_key_exists($methodCall->name->name, $this->eloquentHelperMethods);
    }

    /**
     * @param Expr $expression
     *
     * @return bool
     */
    private function methodImmediatelyReturnsExpression($expression): bool
    {
        $stmts = $this->removeNoOps($this->method->stmts);

        if (count($stmts) !== 1) {
            return false;
        }

        /** @var Return_ $return */
        $return = $stmts[0];
        if (! ($return instanceof Return_)) {
            return false;
        }

        return $return->expr === $expression;
    }

    /**
     * Remove no-op statements. This is mainly used to remove comments
     * that aren't part of another statement.
     *
     * @param $stmts
     *
     * @return array|Node[]
     */
    private function removeNoOps($stmts)
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new class extends NodeVisitorAbstract {
            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Nop) {
                    return NodeTraverser::REMOVE_NODE;
                }
            }
        });

        return $traverser->traverse($stmts);
    }

    /**
     * @param MethodCall $methodCall
     *
     * @throws Exception
     */
    private function parseEloquentMethodCall(MethodCall $methodCall): void
    {
        $type = $methodCall->name->name;
        $args = $this->evaluateArgs($methodCall->args);

        switch ($type) {
            case 'hasOne':
                $this->parseHasOne(...$args);
                break;
            case 'hasMany':
                $this->parseHasMany(...$args);
                break;
            case 'hasOneThrough':
                $this->parseHasOneThrough(...$args);
                break;
            case 'hasManyThrough':
                $this->parseHasManyThrough(...$args);
                break;
            case 'belongsTo':
                $this->parseBelongsTo(...$args);
                break;
            case 'belongsToMany':
                $this->parseBelongsToMany(...$args);
                break;
            default:
                $this->parseUnsupportedRelation();
                break;
        }
    }

    private function evaluateArgs(array $args): array
    {
        $evaluatedArgs = [];

        foreach ($args as $arg) {
            $evaluatedArgs[] = $this->evaluateArg($arg);
        }

        return $evaluatedArgs;
    }

    private function evaluateArg(Arg $arg)
    {
        try {
            return $this->evaluator->evaluateSilently($arg->value);
        } catch (ConstExprEvaluationException $e) {
            $this->relation->hasUnknownsOrLogic = true;

            return;
        }
    }

    private function parseHasOne($related = null, $foreignKey = null, $localKey = null): void
    {
        $this->convertRelationToConcreteType(HasOneRelation::class);

        if ($related === null) {
            $this->relation->hasUnknownsOrLogic = true;
        }

        $this->relation->relatedClass = $related;
        $this->relation->foreignKey = $foreignKey ?: $this->modelHelper->getForeignKey($this->concreteClassName);
        $this->relation->localKey = $localKey ?: $this->modelHelper->getKeyName($this->concreteClassName);
    }

    private function parseHasMany($related = null, $foreignKey = null, $localKey = null): void
    {
        $this->convertRelationToConcreteType(HasManyRelation::class);

        if ($related === null) {
            $this->relation->hasUnknownsOrLogic = true;
        }

        $this->relation->relatedClass = $related;
        $this->relation->foreignKey = $foreignKey ?: $this->modelHelper->getForeignKey($this->concreteClassName);
        $this->relation->localKey = $localKey ?: $this->modelHelper->getKeyName($this->concreteClassName);
    }

    private function parseHasOneThrough(
        $related = null, $through = null, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null
    ): void {
        $this->convertRelationToConcreteType(HasOneThroughRelation::class);

        if ($related === null || $through === null) {
            $this->relation->hasUnknownsOrLogic = true;
        }

        $this->relation->relatedClass = $related;
        $this->relation->through = $through;
        $this->relation->firstKey = $firstKey ?: $this->modelHelper->getForeignKey($this->concreteClassName);
        $this->relation->secondKey = $secondKey ?: $this->modelHelper->getForeignKey($through);
        $this->relation->localKey = $localKey ?: $this->modelHelper->getKeyName($this->concreteClassName);
        $this->relation->secondLocalKey = $secondLocalKey ?: $this->modelHelper->getKeyName($through);
    }

    private function parseHasManyThrough(
        $related = null, $through = null, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null
    ): void {
        $this->convertRelationToConcreteType(HasManyThroughRelation::class);

        if ($related === null || $through === null) {
            $this->relation->hasUnknownsOrLogic = true;
        }

        $this->relation->relatedClass = $related;
        $this->relation->through = $through;
        $this->relation->firstKey = $firstKey ?: $this->modelHelper->getForeignKey($this->concreteClassName);
        $this->relation->secondKey = $secondKey ?: $this->modelHelper->getForeignKey($through);
        $this->relation->localKey = $localKey ?: $this->modelHelper->getKeyName($this->concreteClassName);
        $this->relation->secondLocalKey = $secondLocalKey ?: $this->modelHelper->getKeyName($through);
    }

    private function parseBelongsTo($related = null, $foreignKey = null, $ownerKey = null, $relation = null): void
    {
        $this->convertRelationToConcreteType(BelongsToRelation::class);

        if ($related === null) {
            $this->relation->hasUnknownsOrLogic = true;
        }

        $this->relation->relatedClass = $related;
        $this->relation->relationName = $relation ?: $this->relation->name;
        $this->relation->foreignKey = $foreignKey ?: Str::snake($this->relation->relationName).'_'.$this->modelHelper->getKeyName($related);
        $this->relation->ownerKey = $ownerKey ?: $this->modelHelper->getKeyName($related);
    }

    private function parseBelongsToMany(
        $related = null, $table = null, $foreignPivotKey = null, $relatedPivotKey = null,
        $parentKey = null, $relatedKey = null, $relation = null
    ): void {
        $this->convertRelationToConcreteType(BelongsToManyRelation::class);

        if ($related === null) {
            $this->relation->hasUnknownsOrLogic = true;
        }

        $this->relation->relatedClass = $related;

        $this->relation->table = $table ?: $this->modelHelper->joiningTable($this->concreteClassName, $related);
        $this->relation->foreignPivotKey = $foreignPivotKey ?: $this->modelHelper->getForeignKey($this->concreteClassName);
        $this->relation->relatedPivotKey = $relatedPivotKey ?: $this->modelHelper->getForeignKey($related);
        $this->relation->parentKey = $parentKey ?: $this->modelHelper->getKeyName($this->concreteClassName);
        $this->relation->relatedKey = $relatedKey ?: $this->modelHelper->getKeyName($related);
        $this->relation->relationName = $relation ?: $this->relation->name;
    }

    private function parseUnsupportedRelation($related = null): void
    {
        $this->convertRelationToConcreteType(UnsupportedRelation::class);

        $this->relation->hasUnknownsOrLogic = true;
        $this->relation->relatedClass = $related;
    }

    /**
     * @param string $className
     *
     * @throws Exception
     */
    private function convertRelationToConcreteType(string $className)
    {
        /** @var Relation $class */
        $class = new $className;
        if (! ($class instanceof Relation)) {
            throw new Exception("$className does not extend from Relation.");
        }

        $class->name = $this->relation->name;
        $class->relatedClass = $this->relation->relatedClass;
        $class->hasUnknownsOrLogic = $this->relation->hasUnknownsOrLogic;

        $this->relation = $class;
    }
}
