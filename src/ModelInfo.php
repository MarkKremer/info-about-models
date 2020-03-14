<?php

namespace MarkKremer\InfoAboutModels;

use Illuminate\Support\Collection;
use MarkKremer\InfoAboutModels\Relations\Relation;

class ModelInfo
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $table;

    /**
     * @var Collection|Relation[]
     */
    public $relations;
}
