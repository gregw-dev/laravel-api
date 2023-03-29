<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFlagChangepasswordToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("users", function (Blueprint $objTable) {
            $objTable->boolean("flag_changepassword")->after("remember_token")->default(0);
            $objTable->index("flag_changepassword","idx_flag-changepassword");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("users", function (Blueprint $objTable) {
            $objTable->dropColumn("flag_changepassword");
            $objTable->dropIndex("idx_flag-changepassword");
        });
    }
}
