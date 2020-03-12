<?php

namespace MarkKremer\InfoAboutModels\Tests\Code;

use Illuminate\Database\Eloquent\Model;

class ModelWithDefaultRelations extends Model
{
    public function hasOneRelation()
    {
        return $this->hasOne(Foo::class);
    }

    public function hasManyRelation()
    {
        return $this->hasMany(Foo::class);
    }

    public function hasOneThroughRelation()
    {
        return $this->hasOneThrough(Foo::class, Bar::class);
    }

    public function hasManyThroughRelation()
    {
        return $this->hasManyThrough(Foo::class, Bar::class);
    }

    public function belongsToRelation()
    {
        return $this->belongsTo(Foo::class);
    }

    public function belongsToManyRelation()
    {
        return $this->belongsToMany(Foo::class);
    }
}
