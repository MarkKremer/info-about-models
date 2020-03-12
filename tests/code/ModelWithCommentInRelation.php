<?php

namespace MarkKremer\InfoAboutModels\Tests\Code;

use Illuminate\Database\Eloquent\Model;

class ModelWithCommentInRelation extends Model
{
    public function hasOneRelation()
    {
        // Comment at start of method.
        return $this->hasOne(Foo::class /* comment behind argument */); // Comment after return.
    }
}
