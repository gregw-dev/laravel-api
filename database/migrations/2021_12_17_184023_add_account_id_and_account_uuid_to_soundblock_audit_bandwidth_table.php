<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountIdAndAccountUuidToSoundblockAuditBandwidthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('soundblock_audit_bandwidth', function (Blueprint $objTable) {
            $objTable->unsignedBigInteger("account_id")->index("idx_account-id")->after("row_uuid");
            $objTable->uuid("account_uuid")->index("idx_account-uuid")->after("account_id");
            $objTable->unsignedBigInteger("project_id")->nullable()->change();
            $objTable->uuid("project_uuid")->nullable()->change();

            $objTable->index(["account_id","project_id"],"idx_account-id_project-id");
            $objTable->index(["account_uuid","project_uuid"],"idx_account-uuid_project-uuid");
            $objTable->index(["project_id","account_id"],"idx_project-id_account-id");
            $objTable->index(["project_uuid","account_uuid"],"idx_project-uuid_account-uuid");

            $objTable->index(["account_id","user_id"],"idx_account-id_user-id");
            $objTable->index(["account_uuid","user_uuid"],"idx_account-uuid_user-uuid");
            $objTable->index(["user_id","account_id"],"idx_user-id_account-id");
            $objTable->index(["user_uuid","account_uuid"],"idx_user-uuid_account-uuid");

            $objTable->index(["account_id","flag_action"],"idx_account-id_flag-action");
            $objTable->index(["account_uuid","flag_action"],"idx_account-uuid_flag-action");
            $objTable->index(["flag_action","account_id"],"idx_flag-action_account-id");
            $objTable->index(["flag_action","account_uuid"],"idx_flag-action_account-uuid");


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('soundblock_audit_bandwidth', function (Blueprint $objTable) {
            $objTable->dropIndex("idx_account-id");
            $objTable->dropIndex("idx_account-uuid");
            $objTable->dropIndex("idx_account-id_project-id");
            $objTable->dropIndex("idx_account-uuid_project-uuid");
            $objTable->dropIndex("idx_project-id_account-id");
            $objTable->dropIndex("idx_project-uuid_account-uuid");
            $objTable->dropIndex("idx_account-id_user-id");
            $objTable->dropIndex("idx_account-uuid_user-uuid");
            $objTable->dropIndex("idx_user-id_account-id");
            $objTable->dropIndex("idx_user-uuid_account-uuid");
            $objTable->dropIndex("idx_account-id_flag-action");
            $objTable->dropIndex("idx_account-uuid_flag-action");
            $objTable->dropIndex("idx_flag-action_account-id");
            $objTable->dropIndex("idx_flag-action_account-uuid");

            $objTable->dropColumn('account_id');
            $objTable->dropColumn('account_uuid');
            $objTable->unsignedBigInteger("project_id")->change();
            $objTable->uuid("project_uuid")->change();
        });
    }
}
