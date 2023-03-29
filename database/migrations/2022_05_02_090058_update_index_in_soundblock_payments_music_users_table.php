<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIndexInSoundblockPaymentsMusicUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_payments_music_users", function (Blueprint $objTable) {
            $objTable->dropUnique("uidx_platform-id_project-id_user-id_date-starts_date-ends");
            $objTable->dropUnique("uidx_platform-uuid_project-uuid_user-uuid_date-starts_date-ends");

            $objTable->unique(["platform_id","user_id","project_id","date_starts","date_ends","report_currency"],"uidx_platform-id_project-id_user-id_dates_curr");
            $objTable->unique(["platform_uuid","user_uuid","project_uuid","date_starts","date_ends","report_currency"],"uidx_platform-uuid_project-uuid_user-uuid_dates_curr");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_payments_music_users", function (Blueprint $objTable) {
            $objTable->dropUnique("uidx_platform-id_project-id_user-id_dates_curr");
            $objTable->dropUnique("uidx_platform-uuid_project-uuid_user-uuid_dates_curr");

            $objTable->unique(["platform_id","user_id","project_id","date_starts","date_ends"],"uidx_platform-id_project-id_user-id_date-starts_date-ends");
            $objTable->unique(["platform_uuid","user_uuid","project_uuid","date_starts","date_ends"],"uidx_platform-uuid_project-uuid_user-uuid_date-starts_date-ends");
        });
    }
}
