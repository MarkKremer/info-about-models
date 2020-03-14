<?php

namespace MarkKremer\InfoAboutModels\ModelHelpers;

use Illuminate\Support\Str;

class DefaultsModelHelper implements ModelHelper
{
    /**
     * {@inheritdoc}
     */
    public function getTable(string $model): string
    {
        return Str::snake(Str::pluralStudly(class_basename($model)));
    }

    /**
     * {@inheritdoc}
     */
    public function getForeignKey(string $model): string
    {
        return Str::snake(class_basename($model)).'_'.$this->getKeyName($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyName(string $model): string
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function joiningTable($model, $related): string
    {
        // The joining table name, by convention, is simply the snake cased models
        // sorted alphabetically and concatenated with an underscore, so we can
        // just sort the models and join them together to get the table name.
        $segments = [
            Str::snake(class_basename($related)),
            Str::snake(class_basename($model)),
        ];

        // Now that we have the model names in an array we can just sort them and
        // use the implode function to join them together with an underscores,
        // which is typically used by convention within the database system.
        sort($segments);

        return strtolower(implode('_', $segments));
    }
}
