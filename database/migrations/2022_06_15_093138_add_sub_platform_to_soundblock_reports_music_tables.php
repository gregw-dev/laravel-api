<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubPlatformToSoundblockReportsMusicTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_reports_music_matched", function (Blueprint $objTable) {
            $objTable->unsignedBigInteger("sub_platform_id")->after("platform_uuid")->index("idx_sub-platform-id")->nullable();
            $objTable->uuid("sub_platform_uuid")->after("sub_platform_id")->index("idx_sub-platform-uuid")->nullable();
        });
        Schema::table("soundblock_reports_music_unmatched", function (Blueprint $objTable) {
            $objTable->unsignedBigInteger("sub_platform_id")->after("platform_uuid")->index("idx_sub-platform-id")->nullable();
            $objTable->uuid("sub_platform_uuid")->after("sub_platform_id")->index("idx_sub-platform-uuid")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_reports_music_matched", function (Blueprint $objTable) {
            $objTable->dropColumn("sub_platform_id");
            $objTable->dropColumn("sub_platform_uuid");
            $objTable->dropIndex("idx_sub-platform-id");
            $objTable->dropIndex("idx_sub-platform-uuid");
        });
        Schema::table("soundblock_reports_music_unmatched", function (Blueprint $objTable) {
            $objTable->dropColumn("sub_platform_id");
            $objTable->dropColumn("sub_platform_uuid");
            $objTable->dropIndex("idx_sub-platform-id");
            $objTable->dropIndex("idx_sub-platform-uuid");
        });
    }
}
