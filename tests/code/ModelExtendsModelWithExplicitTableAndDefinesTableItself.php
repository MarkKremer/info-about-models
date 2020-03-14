<?php

namespace MarkKremer\InfoAboutModels\Tests\Code;

class ModelExtendsModelWithExplicitTableAndDefinesTableItself extends ModelWithExplicitTable
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'own_table';
}
