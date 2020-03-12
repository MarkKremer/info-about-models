<?php

namespace MarkKremer\InfoAboutModels\Relations;

class HasOneThrough extends Relation
{
    /**
     * @var string
     */
    public $through;

    /**
     * @var string
     */
    public $firstKey;

    /**
     * @var string
     */
    public $secondKey;

    /**
     * @var string
     */
    public $localKey;

    /**
     * @var string
     */
    public $secondLocalKey;
}
