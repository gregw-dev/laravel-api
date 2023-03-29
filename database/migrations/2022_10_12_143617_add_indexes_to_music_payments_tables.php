<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToMusicPaymentsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_payments_music_accounts", function (Blueprint $objTable) {
            $objTable->index(["account_uuid", "stamp_deleted_at"], "idx_account_deleted");
            $objTable->index(["account_uuid", "platform_uuid", "stamp_deleted_at"], "idx_account_platform_deleted");
            $objTable->index(["account_uuid", "platform_uuid", "date_starts", "date_ends", "stamp_deleted_at"], "idx_account_platform_dates_deleted");
        });
        Schema::table("soundblock_payments_music_projects", function (Blueprint $objTable) {
            $objTable->index(["project_uuid", "stamp_deleted_at"], "idx_project_deleted");
            $objTable->index(["project_uuid", "platform_uuid", "stamp_deleted_at"], "idx_project_platform_deleted");
            $objTable->index(["project_uuid", "platform_uuid", "date_starts", "date_ends", "stamp_deleted_at"], "idx_project_platform_dates_deleted");
        });
        Schema::table("soundblock_payments_music_users", function (Blueprint $objTable) {
            $objTable->index(["project_uuid", "user_uuid", "stamp_deleted_at"], "idx_project_user_deleted");
            $objTable->index(["project_uuid", "user_uuid", "platform_uuid", "stamp_deleted_at"], "idx_project_user_platform_deleted");
            $objTable->index(["project_uuid", "user_uuid", "platform_uuid", "date_starts", "date_ends", "stamp_deleted_at"], "idx_project_user_platform_dates_deleted");
        });
        Schema::table("soundblock_reports_music_matched", function (Blueprint $objTable) {
            $objTable->index(["project_uuid", "stamp_deleted_at"], "idx_project_deleted");
            $objTable->index(["project_uuid", "platform_uuid", "stamp_deleted_at"], "idx_project_platform_deleted");
            $objTable->index(["project_uuid", "platform_uuid", "date_starts", "date_ends", "stamp_deleted_at"], "idx_project_platform_dates_deleted");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_payments_music_accounts", function (Blueprint $objTable) {
            $objTable->dropIndex("idx_account_deleted");
            $objTable->dropIndex("idx_account_platform_deleted");
            $objTable->dropIndex("idx_account_platform_dates_deleted");
        });
        Schema::table("soundblock_payments_music_projects", function (Blueprint $objTable) {
            $objTable->dropIndex("idx_project_deleted");
            $objTable->dropIndex("idx_project_platform_deleted");
            $objTable->dropIndex("idx_project_platform_dates_deleted");
        });
        Schema::table("soundblock_payments_music_users", function (Blueprint $objTable) {
            $objTable->dropIndex("idx_project_user_deleted");
            $objTable->dropIndex("idx_project_user_platform_deleted");
            $objTable->dropIndex("idx_project_user_platform_dates_deleted");
        });
        Schema::table("soundblock_reports_music_matched", function (Blueprint $objTable) {
            $objTable->dropIndex("idx_project_deleted");
            $objTable->dropIndex("idx_project_platform_deleted");
            $objTable->dropIndex("idx_project_platform_dates_deleted");
        });
    }
}
