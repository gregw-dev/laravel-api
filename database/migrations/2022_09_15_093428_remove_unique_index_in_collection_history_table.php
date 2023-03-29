<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueIndexInCollectionHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_collections_history", function (Blueprint $objTable) {
            $objTable->dropUnique("uidx_collection-id");
            $objTable->dropUnique("uidx_collection-uuid");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_collections_history", function (Blueprint $objTable) {
            $objTable->unique("collection_id", "uidx_collection-id");
            $objTable->unique("collection_uuid", "uidx_collection-uuid");
        });
    }
}
