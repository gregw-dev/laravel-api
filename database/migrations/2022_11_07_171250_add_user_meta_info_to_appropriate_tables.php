<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserMetaInfoToAppropriateTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("oauth_access_tokens", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_accounts", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_artists", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_collections", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_files", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_ledger", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_projects", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_projects_artists", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_projects_contracts", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_projects_contracts_history", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_projects_contracts_users", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_projects_deployments", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_projects_deployments_history", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("soundblock_tracks", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("support_tickets", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("support_tickets_messages", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
        Schema::table("users", function (Blueprint $objTable) {
            $objTable->string("remote_addr")->index("idx_remote-addr")->nullable();
            $objTable->string("remote_host")->index("idx_remote-host")->nullable();
            $objTable->string("remote_agent")->index("idx_remote-agent")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("oauth_access_tokens", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_accounts", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_artists", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_collections", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_files", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_ledger", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_projects", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_projects_artists", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_projects_contracts", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_projects_contracts_history", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_projects_contracts_users", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_projects_deployments", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_projects_deployments_history", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("soundblock_tracks", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("support_tickets", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("support_tickets_messages", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
        Schema::table("users", function (Blueprint $objTable) {
            $objTable->dropColumn("remote_addr");
            $objTable->dropColumn("remote_host");
            $objTable->dropColumn("remote_agent");
        });
    }
}
