<?php

namespace MarkKremer\InfoAboutModels\Relations;

class BelongsTo extends Relation
{
    /**
     * @var string
     */
    public $foreignKey;

    /**
     * @var string
     */
    public $ownerKey;

    /**
     * @var string
     */
    public $relationName;
}
