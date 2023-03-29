<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersContactPostalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("users_contact_postal", function (Blueprint $objTable) {
            $objTable->string("postal_state")->after("postal_city")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("users_contact_postal", function (Blueprint $objTable) {
            $objTable->dropColumn("postal_state");
        });
    }
}
