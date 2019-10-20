<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReliqArts\GuidedImage\Contract\ConfigProvider;

class CreateGuidedImageablesTable extends Migration
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
        $tableName = $this->configProvider->getImageablesTableName();
        $imagesTableName = $this->configProvider->getImagesTableName();

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($imagesTableName) {
            $table->increments('id');
            $table->integer('image_id')->unsigned();
            $table->foreign('image_id')
                ->references('id')
                ->on($imagesTableName)
                ->onDelete('CASCADE');
            $table->integer('imageable_id');
            $table->string('imageable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $tableName = $this->configProvider->getImageablesTableName();

        Schema::dropIfExists($tableName);
    }
}
