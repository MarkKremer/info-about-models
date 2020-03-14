<?php

namespace MarkKremer\InfoAboutModels\Tests\Code;

use Illuminate\Database\Eloquent\Model;

class ModelWithTableNameInTrait extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'explicit_table';
}
