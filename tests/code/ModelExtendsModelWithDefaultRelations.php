<?php

namespace MarkKremer\InfoAboutModels\Tests\Code;

class ModelExtendsModelWithDefaultRelations extends ModelWithDefaultRelations
{
    public function modelsOwnHasOneRelation()
    {
        return $this->hasOne(Foo::class);
    }

    public function hasManyRelation()
    {
        // This method overrides the parent method and returns a relation with other attributes.
        return $this->hasMany(Foo::class, 'model_uuid', 'uuid');
    }
}
