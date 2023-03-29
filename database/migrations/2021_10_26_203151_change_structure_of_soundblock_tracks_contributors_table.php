<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStructureOfSoundblockTracksContributorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_tracks_contributors", function (Blueprint $objTable) {
            $objTable->string("contributor_name")->nullable()->change();
            $objTable->unsignedBigInteger("contributor_role_id")->after("contributor_name")->index("idx_contributor-role-id");
            $objTable->uuid("contributor_role_uuid")->after("contributor_role_id")->index("idx_contributor-role-uuid");

            $objTable->dropIndex("uidx_file-id_contributor-id");
            $objTable->dropIndex("uidx_file-uuid_contributor-uuid");
            $objTable->dropIndex("uidx_track-id_contributor-id");
            $objTable->dropIndex("uidx_track-uuid_contributor-uuid");

            $objTable->unique(["file_id", "contributor_id", "contributor_role_id"], "uidx_file-id_contributor-id_contributor-role-id");
            $objTable->unique(["file_uuid", "contributor_uuid", "contributor_role_uuid"], "uidx_file-uuid_contributor-uuid_contributor-role-uuid");
            $objTable->unique(["track_id", "contributor_id", "contributor_role_id"], "uidx_track-id_contributor-id_contributor-role-id");
            $objTable->unique(["track_uuid", "contributor_uuid", "contributor_role_uuid"], "uidx_track-uuid_contributor-uuid_contributor-role-uuid");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_tracks_contributors", function (Blueprint $objTable) {
            $objTable->string("contributor_name")->change();
            $objTable->dropIndex("idx_contributor-role-id");
            $objTable->dropIndex("idx_contributor-role-uuid");
            $objTable->dropColumn("contributor_role_id");
            $objTable->dropColumn("contributor_role_uuid");

            $objTable->dropIndex("uidx_file-id_contributor-id_contributor-role-id");
            $objTable->dropIndex("uidx_file-uuid_contributor-uuid_contributor-role-uuid");
            $objTable->dropIndex("uidx_track-id_contributor-id_contributor-role-id");
            $objTable->dropIndex("uidx_track-uuid_contributor-uuid_contributor-role-uuid");

            $objTable->unique(["file_id", "contributor_id"], "uidx_file-id_contributor-id");
            $objTable->unique(["file_uuid", "contributor_uuid"], "uidx_file-uuid_contributor-uuid");
            $objTable->unique(["track_id", "contributor_id"], "uidx_track-id_contributor-id");
            $objTable->unique(["track_uuid", "contributor_uuid"], "uidx_track-uuid_contributor-uuid");
        });
    }
}
