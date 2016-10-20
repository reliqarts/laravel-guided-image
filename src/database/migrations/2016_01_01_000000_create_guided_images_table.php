<?php

/*
 * This file is part of the GuidedImage package.
 *
 * (c) Patrick Reid <reliq@reliqarts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use ReliQArts\GuidedImage\Helpers\SchemaHelper;

class CreateGuidedImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $table = SchemaHelper::getImageTable();
        if (!Schema::hasTable($table))

        Schema::create($table, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50);
            $table->string('mime_type', 20);
            $table->string('extension', 10);
            $table->integer('size');
            $table->integer('height');
            $table->integer('width');
            $table->string('location');
            $table->string('full_path');
            $table->timestamps();
            $table->integer('creator_id')->unsigned();
            $table->foreign('creator_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table = SchemaHelper::getImageTable();
        Schema::dropIfExists($table);
    }
}
