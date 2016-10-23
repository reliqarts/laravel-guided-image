<?php

namespace ReliQArts\GuidedImage\Helpers;

use Config;

class SchemaHelper
{
    /**
     * Get guided image table.
     */
    public static function getImageTable()
    {
        return Config::get('guidedimage.database.image_table', 'images');
    }

    /**
     * Get guided imageables table.
     */
    public static function getImageablesTable()
    {
        return Config::get('guidedimage.database.imageables_table', 'imageables');
    }
}
