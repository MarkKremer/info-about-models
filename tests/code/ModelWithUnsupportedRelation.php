<?php

namespace MarkKremer\InfoAboutModels\Tests\Code;

use Illuminate\Database\Eloquent\Model;

class ModelWithUnsupportedRelation extends Model
{
    public function morphToRelation()
    {
        return $this->morphTo();
    }
}
