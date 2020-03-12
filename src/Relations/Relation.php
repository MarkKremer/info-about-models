<?php

namespace MarkKremer\InfoAboutModels\Relations;

class Relation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string|null
     */
    public $relatedClass;

    /**
     * @var bool
     */
    public $hasUnknownsOrLogic = false;
}
