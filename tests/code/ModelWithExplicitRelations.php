<?php

namespace MarkKremer\InfoAboutModels\Tests\Code;

use Illuminate\Database\Eloquent\Model;

class ModelWithExplicitRelations extends Model
{
    public function hasOneRelation()
    {
        return $this->hasOne(Foo::class, 'model_uuid', 'uuid');
    }

    public function hasManyRelation()
    {
        return $this->hasMany(Foo::class, 'model_uuid', 'uuid');
    }

    public function hasOneThroughRelation()
    {
        return $this->hasOneThrough(Foo::class, Bar::class, 'model_uuid', 'bar_uuid', 'uuid_1', 'uuid_2');
    }

    public function hasManyThroughRelation()
    {
        return $this->hasManyThrough(Foo::class, Bar::class, 'model_uuid', 'bar_uuid', 'uuid_1', 'uuid_2');
    }

    public function belongsToRelation()
    {
        return $this->belongsTo(Foo::class, 'foo_uuid', 'uuid', 'belongs_to');
    }

    public function belongsToManyRelation()
    {
        return $this->belongsToMany(Foo::class, 'foo_model', 'model_uuid', 'foo_uuid', 'uuid_1', 'uuid_2', 'belongs_to_many');
    }
}
