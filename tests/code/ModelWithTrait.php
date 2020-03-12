<?php

namespace MarkKremer\InfoAboutModels\Tests\Code;

use Illuminate\Database\Eloquent\Model;

class ModelWithTrait extends Model
{
    use TraitContainingRelation;

    public function hasOneRelation()
    {
        return $this->hasOne(Foo::class);
    }
}
