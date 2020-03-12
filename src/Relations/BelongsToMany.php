<?php

namespace MarkKremer\InfoAboutModels\Relations;

class BelongsToMany extends Relation
{
    /**
     * @var string
     */
    public $table;

    /**
     * @var string
     */
    public $foreignPivotKey;

    /**
     * @var string
     */
    public $relatedPivotKey;

    /**
     * @var string
     */
    public $parentKey;

    /**
     * @var string
     */
    public $relatedKey;

    /**
     * @var string
     */
    public $relationName;
}
