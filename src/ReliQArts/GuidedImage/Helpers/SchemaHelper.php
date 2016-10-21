<?php

/*
 * This file is part of the GuidedImage package.
 *
 * (c) Patrick Reid <reliq@reliqarts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
