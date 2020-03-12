<?php

namespace MarkKremer\InfoAboutModels\Tests;

use MarkKremer\InfoAboutModels\ModelParser;
use MarkKremer\InfoAboutModels\Relations\BelongsTo;
use MarkKremer\InfoAboutModels\Relations\BelongsToMany;
use MarkKremer\InfoAboutModels\Relations\HasMany;
use MarkKremer\InfoAboutModels\Relations\HasManyThrough;
use MarkKremer\InfoAboutModels\Relations\HasOne;
use MarkKremer\InfoAboutModels\Relations\HasOneThrough;
use MarkKremer\InfoAboutModels\Relations\Relation;
use MarkKremer\InfoAboutModels\Relations\UnsupportedRelation;
use MarkKremer\InfoAboutModels\Tests\Code\Bar;
use MarkKremer\InfoAboutModels\Tests\Code\Foo;
use MarkKremer\InfoAboutModels\Tests\Code\ModelExtendsModelWithDefaultRelations;
use MarkKremer\InfoAboutModels\Tests\Code\ModelWithCommentInRelation;
use MarkKremer\InfoAboutModels\Tests\Code\ModelWithDefaultRelations;
use MarkKremer\InfoAboutModels\Tests\Code\ModelWithExplicitRelations;
use MarkKremer\InfoAboutModels\Tests\Code\ModelWithTrait;
use MarkKremer\InfoAboutModels\Tests\Code\ModelWithUnsupportedRelation;
use Orchestra\Testbench\TestCase;
use Traversable;

class TestModelParser extends TestCase
{
    /**
     * @test
     */
    public function it_contains_the_unqualified_class_name()
    {
        $model = (new ModelParser())->parseClass(Foo::class);

        $this->assertEquals('Foo', $model->name);
    }

    /**
     * @test
     */
    public function it_contains_the_fully_qualified_class_name()
    {
        $model = (new ModelParser())->parseClass(Foo::class);

        $this->assertEquals(Foo::class, $model->class);
    }

    /**
     * @test
     */
    public function the_parsed_has_one_defaults_match_the_expected_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $expected = new HasOne();
        $expected->name = 'hasOneRelation';
        $expected->relatedClass = Foo::class;
        $expected->foreignKey = 'model_with_default_relations_id';
        $expected->localKey = 'id';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_has_one_defaults_match_the_eloquent_defaults()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $eloquentRelation = $this->getHasOneRelationFromEloquent(ModelWithDefaultRelations::class, 'hasOneRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_has_one_explicit_values_match_the_eloquent_explicit_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithExplicitRelations::class);

        $eloquentRelation = $this->getHasOneRelationFromEloquent(ModelWithExplicitRelations::class, 'hasOneRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    private function getHasOneRelationFromEloquent(string $modelClass, string $relationName): Relation
    {
        /** @var \Illuminate\Database\Eloquent\Relations\HasOne $eloquentRelation */
        $eloquentRelation = (new $modelClass)->$relationName();

        $relation = new HasOne();
        $relation->name = $relationName;
        $relation->relatedClass = get_class($eloquentRelation->getRelated());
        $relation->foreignKey = $eloquentRelation->getForeignKeyName();
        $relation->localKey = $eloquentRelation->getLocalKeyName();
        $relation->hasUnknownsOrLogic = false;

        return $relation;
    }

    /**
     * @test
     */
    public function the_parsed_has_many_defaults_match_the_expected_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $expected = new HasMany();
        $expected->name = 'hasManyRelation';
        $expected->relatedClass = Foo::class;
        $expected->foreignKey = 'model_with_default_relations_id';
        $expected->localKey = 'id';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_has_many_defaults_match_the_eloquent_defaults()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $eloquentRelation = $this->getHasManyRelationFromEloquent(ModelWithDefaultRelations::class, 'hasManyRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_has_many_explicit_values_match_the_eloquent_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithExplicitRelations::class);

        $eloquentRelation = $this->getHasManyRelationFromEloquent(ModelWithExplicitRelations::class, 'hasManyRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    private function getHasManyRelationFromEloquent(string $modelClass, string $relationName): Relation
    {
        /** @var \Illuminate\Database\Eloquent\Relations\HasMany $eloquentRelation */
        $eloquentRelation = (new $modelClass)->$relationName();

        $relation = new HasMany();
        $relation->name = $relationName;
        $relation->relatedClass = get_class($eloquentRelation->getRelated());
        $relation->foreignKey = $eloquentRelation->getForeignKeyName();
        $relation->localKey = $eloquentRelation->getLocalKeyName();
        $relation->hasUnknownsOrLogic = false;

        return $relation;
    }

    /**
     * @test
     */
    public function the_parsed_has_one_through_defaults_match_the_expected_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $expected = new HasOneThrough();
        $expected->name = 'hasOneThroughRelation';
        $expected->relatedClass = Foo::class;
        $expected->through = Bar::class;
        $expected->firstKey = 'model_with_default_relations_id';
        $expected->secondKey = 'bar_id';
        $expected->localKey = 'id';
        $expected->secondLocalKey = 'id';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_has_one_through_defaults_match_the_eloquent_defaults()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $eloquentRelation = $this->getHasOneThroughRelationFromEloquent(ModelWithDefaultRelations::class, 'hasOneThroughRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_has_one_through_explicit_values_match_the_eloquent_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithExplicitRelations::class);

        $eloquentRelation = $this->getHasOneThroughRelationFromEloquent(ModelWithExplicitRelations::class, 'hasOneThroughRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    private function getHasOneThroughRelationFromEloquent(string $modelClass, string $relationName): Relation
    {
        /** @var \Illuminate\Database\Eloquent\Relations\HasOneThrough $eloquentRelation */
        $eloquentRelation = (new $modelClass)->$relationName();

        $relation = new HasOneThrough();
        $relation->name = $relationName;
        $relation->relatedClass = get_class($eloquentRelation->getRelated());
        $relation->through = get_class($eloquentRelation->getParent());
        $relation->firstKey = $eloquentRelation->getFirstKeyName();
        $relation->secondKey = $eloquentRelation->getForeignKeyName();
        $relation->localKey = $eloquentRelation->getLocalKeyName();
        $relation->secondLocalKey = $eloquentRelation->getSecondLocalKeyName();
        $relation->hasUnknownsOrLogic = false;

        return $relation;
    }

    /**
     * @test
     */
    public function the_parsed_has_many_through_defaults_match_the_expected_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $expected = new HasManyThrough();
        $expected->name = 'hasManyThroughRelation';
        $expected->relatedClass = Foo::class;
        $expected->through = Bar::class;
        $expected->firstKey = 'model_with_default_relations_id';
        $expected->secondKey = 'bar_id';
        $expected->localKey = 'id';
        $expected->secondLocalKey = 'id';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_has_many_through_defaults_match_the_eloquent_defaults()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $eloquentRelation = $this->getHasManyThroughRelationFromEloquent(ModelWithDefaultRelations::class, 'hasManyThroughRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_has_many_through_explicit_values_match_the_eloquent_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithExplicitRelations::class);

        $eloquentRelation = $this->getHasManyThroughRelationFromEloquent(ModelWithExplicitRelations::class, 'hasManyThroughRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    private function getHasManyThroughRelationFromEloquent(string $modelClass, string $relationName): Relation
    {
        /** @var \Illuminate\Database\Eloquent\Relations\HasManyThrough $eloquentRelation */
        $eloquentRelation = (new $modelClass)->$relationName();

        $relation = new HasManyThrough();
        $relation->name = $relationName;
        $relation->relatedClass = get_class($eloquentRelation->getRelated());
        $relation->through = get_class($eloquentRelation->getParent());
        $relation->firstKey = $eloquentRelation->getFirstKeyName();
        $relation->secondKey = $eloquentRelation->getForeignKeyName();
        $relation->localKey = $eloquentRelation->getLocalKeyName();
        $relation->secondLocalKey = $eloquentRelation->getSecondLocalKeyName();
        $relation->hasUnknownsOrLogic = false;

        return $relation;
    }

    /**
     * @test
     */
    public function the_parsed_belongs_to_defaults_match_the_expected_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $expected = new BelongsTo();
        $expected->name = 'belongsToRelation';
        $expected->relatedClass = Foo::class;
        $expected->foreignKey = 'belongs_to_relation_id';
        $expected->ownerKey = 'id';
        $expected->relationName = 'belongsToRelation';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_belongs_to_defaults_match_the_eloquent_defaults()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $eloquentRelation = $this->getBelongsToRelationFromEloquent(ModelWithDefaultRelations::class, 'belongsToRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_belongs_to_explicit_values_match_the_eloquent_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithExplicitRelations::class);

        $eloquentRelation = $this->getBelongsToRelationFromEloquent(ModelWithExplicitRelations::class, 'belongsToRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    private function getBelongsToRelationFromEloquent(string $modelClass, string $relationName): Relation
    {
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo $eloquentRelation */
        $eloquentRelation = (new $modelClass)->$relationName();

        $relation = new BelongsTo();
        $relation->name = $relationName;
        $relation->relatedClass = get_class($eloquentRelation->getRelated());
        $relation->foreignKey = $eloquentRelation->getForeignKeyName();
        $relation->ownerKey = $eloquentRelation->getOwnerKeyName();
        $relation->relationName = $eloquentRelation->getRelationName();
        $relation->hasUnknownsOrLogic = false;

        return $relation;
    }

    /**
     * @test
     */
    public function the_parsed_belongs_to_many_defaults_match_the_expected_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $expected = new BelongsToMany();
        $expected->name = 'belongsToManyRelation';
        $expected->relatedClass = Foo::class;
        $expected->table = 'foo_model_with_default_relations';
        $expected->foreignPivotKey = 'model_with_default_relations_id';
        $expected->relatedPivotKey = 'foo_id';
        $expected->parentKey = 'id';
        $expected->relatedKey = 'id';
        $expected->relationName = 'belongsToManyRelation';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_belongs_to_many_defaults_match_the_eloquent_defaults()
    {
        $model = (new ModelParser())->parseClass(ModelWithDefaultRelations::class);

        $eloquentRelation = $this->getBelongsToManyRelationFromEloquent(ModelWithDefaultRelations::class, 'belongsToManyRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_belongs_to_many_explicit_values_match_the_eloquent_values()
    {
        $model = (new ModelParser())->parseClass(ModelWithExplicitRelations::class);

        $eloquentRelation = $this->getBelongsToManyRelationFromEloquent(ModelWithExplicitRelations::class, 'belongsToManyRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    private function getBelongsToManyRelationFromEloquent(string $modelClass, string $relationName): Relation
    {
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsToMany $eloquentRelation */
        $eloquentRelation = (new $modelClass)->$relationName();

        $relation = new BelongsToMany();
        $relation->name = $relationName;
        $relation->relatedClass = get_class($eloquentRelation->getRelated());
        $relation->table = $eloquentRelation->getTable();
        $relation->foreignPivotKey = $eloquentRelation->getForeignPivotKeyName();
        $relation->relatedPivotKey = $eloquentRelation->getRelatedPivotKeyName();
        $relation->parentKey = $eloquentRelation->getParentKeyName();
        $relation->relatedKey = $eloquentRelation->getRelatedKeyName();
        $relation->relationName = $eloquentRelation->getRelationName();
        $relation->hasUnknownsOrLogic = false;

        return $relation;
    }

    /**
     * @test
     */
    public function the_parsed_morph_to_is_returned_as_an_unsupported_relation()
    {
        $model = (new ModelParser())->parseClass(ModelWithUnsupportedRelation::class);

        $expected = new UnsupportedRelation();
        $expected->name = 'morphToRelation';
        $expected->relatedClass = null;
        $expected->hasUnknownsOrLogic = true;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function it_ignores_comments()
    {
        $model = (new ModelParser())->parseClass(ModelWithCommentInRelation::class);

        $expected = new HasOne();
        $expected->name = 'hasOneRelation';
        $expected->relatedClass = Foo::class;
        $expected->foreignKey = 'model_with_comment_in_relation_id';
        $expected->localKey = 'id';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_relation_of_the_parent_class_match_the_expected_values()
    {
        $model = (new ModelParser())->parseClass(ModelExtendsModelWithDefaultRelations::class);

        $expected = new HasOneThrough();
        $expected->name = 'hasOneThroughRelation';
        $expected->relatedClass = Foo::class;
        $expected->through = Bar::class;
        $expected->firstKey = 'model_extends_model_with_default_relations_id';
        $expected->secondKey = 'bar_id';
        $expected->localKey = 'id';
        $expected->secondLocalKey = 'id';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function the_parsed_relation_of_the_parent_class_match_the_eloquent_values()
    {
        $model = (new ModelParser())->parseClass(ModelExtendsModelWithDefaultRelations::class);

        $eloquentRelation = $this->getHasOneThroughRelationFromEloquent(ModelExtendsModelWithDefaultRelations::class, 'hasOneThroughRelation');

        $this->assertContainsRelation($eloquentRelation, $model->relations);
    }

    /**
     * @test
     */
    public function it_can_parse_the_relation_of_a_class_itself_if_it_extends_another_class()
    {
        $model = (new ModelParser())->parseClass(ModelExtendsModelWithDefaultRelations::class);

        $expected = new HasOne();
        $expected->name = 'modelsOwnHasOneRelation';
        $expected->relatedClass = Foo::class;
        $expected->foreignKey = 'model_extends_model_with_default_relations_id';
        $expected->localKey = 'id';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function it_parses_the_overwritten_relation()
    {
        $model = (new ModelParser())->parseClass(ModelExtendsModelWithDefaultRelations::class);

        $expected = new HasMany();
        $expected->name = 'hasManyRelation';
        $expected->relatedClass = Foo::class;
        $expected->foreignKey = 'model_uuid';
        $expected->localKey = 'uuid';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function it_does_not_return_an_overwritten_relation()
    {
        $model = (new ModelParser())->parseClass(ModelExtendsModelWithDefaultRelations::class);

        $this->assertContainsRelationExactlyOnce('hasManyRelation', $model->relations);
    }

    /**
     * @test
     */
    public function it_can_parse_the_relation_in_a_trait()
    {
        $model = (new ModelParser())->parseClass(ModelWithTrait::class);

        $expected = new HasOne();
        $expected->name = 'traitRelation';
        $expected->relatedClass = Foo::class;
        $expected->foreignKey = 'model_with_trait_id';
        $expected->localKey = 'id';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @test
     */
    public function it_can_parse_the_relation_of_a_class_itself_if_it_uses_a_trait()
    {
        $model = (new ModelParser())->parseClass(ModelWithTrait::class);

        $expected = new HasOne();
        $expected->name = 'hasOneRelation';
        $expected->relatedClass = Foo::class;
        $expected->foreignKey = 'model_with_trait_id';
        $expected->localKey = 'id';
        $expected->hasUnknownsOrLogic = false;

        $this->assertContainsRelation($expected, $model->relations);
    }

    /**
     * @param Relation               $needle
     * @param Traversable|Relation[] $haystack
     */
    private function assertContainsRelation(Relation $needle, $haystack): void
    {
        $this->assertIsIterable($haystack);

        $relation = collect($haystack)->first(function ($relation) use ($needle): bool {
            return $relation instanceof Relation && $relation->name === $needle->name;
        });

        if ($relation === null) {
            $this->fail("Could not find relation with name \"$needle->name\" in:\n".var_export(iterator_to_array($haystack), true));
        }

        $this->assertEquals($needle, $relation);
    }

    private function assertContainsRelationExactlyOnce(string $relationName, $haystack): void
    {
        $this->assertIsIterable($haystack);

        $count = collect($haystack)->filter(function (Relation $relation) use ($relationName): bool {
            return $relation instanceof Relation && $relation->name === $relationName;
        })->count();

        $this->assertEquals(1, $count, "Relation $relationName was not found exactly once.");
    }
}
