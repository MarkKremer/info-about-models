<?php

namespace MarkKremer\InfoAboutModels\Tests\Code;

trait TraitContainingRelation
{
    public function traitRelation()
    {
        return $this->hasOne(Foo::class);
    }
}
