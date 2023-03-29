<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFraudScoreToProjectsAndOauthTokensTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("soundblock_projects", function (Blueprint $objTable) {
            $objTable->integer("fraud_score")->nullable()->index("idx_fraud-score");
        });
        Schema::table("oauth_access_tokens", function (Blueprint $objTable) {
            $objTable->integer("fraud_score")->nullable()->index("idx_fraud-score");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("soundblock_projects", function (Blueprint $objTable) {
            $objTable->dropColumn("fraud_score");
        });
        Schema::table("oauth_access_tokens", function (Blueprint $objTable) {
            $objTable->dropColumn("fraud_score");
        });
    }
}
