<?php

namespace MarkKremer\InfoAboutModels\Relations;

class HasOne extends Relation
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
