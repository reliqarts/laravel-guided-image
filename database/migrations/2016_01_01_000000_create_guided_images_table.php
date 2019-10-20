<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReliqArts\GuidedImage\Contract\ConfigProvider;

class CreateGuidedImagesTable extends Migration
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * CreateGuidedImagesTable constructor.
     */
    public function __construct()
    {
        $this->configProvider = resolve(ConfigProvider::class);
    }

    /**
     * Run the migrations.
     */
    public function up()
    {
        $tableName = $this->configProvider->getImagesTableName();

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
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
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $tableName = $this->configProvider->getImagesTableName();

        Schema::dropIfExists($tableName);
    }
}
