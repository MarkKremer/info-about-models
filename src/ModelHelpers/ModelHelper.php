<?php

namespace MarkKremer\InfoAboutModels\ModelHelpers;

interface ModelHelper
{
    /**
     * Get the default foreign key name for the model.
     *
     * @param string $model
     *
     * @return string
     */
    public function getForeignKey(string $model): string;

    /**
     * Get the primary key for the model.
     *
     * @param string $model
     *
     * @return string
     */
    public function getKeyName(string $model): string;

    /**
     * Get the joining table name for a many-to-many relation.
     *
     * @param string $model
     * @param string $related
     *
     * @return string
     */
    public function joiningTable(string $model, string $related): string;
}
