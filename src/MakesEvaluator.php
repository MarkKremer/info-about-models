<?php

namespace MarkKremer\InfoAboutModels;

use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;

trait MakesEvaluator
{
    protected function makeEvaluator(): ConstExprEvaluator
    {
        return new ConstExprEvaluator(function (Expr $expr) {
            if ($expr instanceof ClassConstFetch) {
                return $this->fetchClassConstant($expr);
            }

            throw new ConstExprEvaluationException("Expression of type {$expr->getType()} cannot be evaluated");
        });
    }

    private function fetchClassConstant(ClassConstFetch $classConstFetch): string
    {
        return $classConstFetch->class->toString();
    }
}
