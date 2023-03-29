<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameSoundblockReportsMusicRevenueUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename("soundblock_reports_music_revenue_users","soundblock_payments_music_users");
        Schema::table("soundblock_payments_music_users", function (Blueprint $objTable) {
            $objTable->foreignId("platform_id")->after("row_uuid")->index("idx_platform-id");
            $objTable->foreignUuid("platform_uuid")->after("platform_id")->index("idx_platform-uuid");

            $objTable->unique(["platform_id","user_id","project_id","date_starts","date_ends"],"uidx_platform-id_project-id_user-id_date-starts_date-ends");
            $objTable->unique(["platform_uuid","user_uuid","project_uuid","date_starts","date_ends"],"uidx_platform-uuid_project-uuid_user-uuid_date-starts_date-ends");        });
        Schema::rename("soundblock_reports_revenue_unmatched", "soundblock_reports_music_unmatched");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_payments_music_users", function (Blueprint $objTable) {
            $objTable->dropIndex("uidx_platform-id_project-id_user-id_date-starts_date-ends");
            $objTable->dropIndex("uidx_platform-uuid_project-uuid_user-uuid_date-starts_date-ends");

            $objTable->dropColumn("platform_id");
            $objTable->dropColumn("platform_uuid");
        });

        Schema::rename("soundblock_payments_music_users","soundblock_reports_music_revenue_users");
        Schema::rename("soundblock_reports_music_unmatched","soundblock_reports_revenue_unmatched");
    }
}
