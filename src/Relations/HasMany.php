<?php

namespace MarkKremer\InfoAboutModels\Relations;

class HasMany extends Relation
{
    /**
     * @var string
     */
    public $foreignKey;

    /**
     * @var string
     */
    public $localKey;
}
