<?php

use App\Models\BaseModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundblockConferencesParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("soundblock_conferences_participants", function (Blueprint $objTable) {
            $objTable->bigIncrements("participant_id")->unique("uidx_participant-id");
            $objTable->uuid("participant_uuid")->unique("uidx_participant-uuid");

            $objTable->unsignedBigInteger("conference_id")->index("idx_conference-id")->nullable();
            $objTable->uuid("conference_uuid")->index("idx_conference-uuid")->nullable();

            $objTable->unsignedBigInteger("user_id")->index("idx_user-id")->nullable();
            $objTable->uuid("user_uuid")->index("idx_user-uuid")->nullable();
            $objTable->string("email_address")->default("");

            $objTable->unsignedBigInteger("participant_bandwidth")->nullable();
            $objTable->unsignedBigInteger("participant_diskspace")->nullable();
            $objTable->timeStamp("participant_ping")->nullable();

            $objTable->string("participating_sid");
            $objTable->string("recording_audio_sid")->default("");
            $objTable->string("recording_video_sid")->default("");

            $objTable->unsignedBigInteger("room_duration")->nullable();
            $objTable->timeStamp("room_start");
            $objTable->timeStamp("room_stop")->nullable();

            $objTable->string("media_audio_url")->default("");
            $objTable->string("media_video_url")->default("");
            $objTable->text("media_audio_location")->default("");
            $objTable->text("media_video_location")->default("");

            $objTable->boolean("flag_record")->default(0);

            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED)->nullable()->index(BaseModel::STAMP_CREATED);
            $objTable->timestamp(BaseModel::CREATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_CREATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED)->nullable()->index(BaseModel::STAMP_UPDATED);
            $objTable->timestamp(BaseModel::UPDATED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_UPDATED_BY)->nullable();

            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED)->nullable()->index(BaseModel::STAMP_DELETED);
            $objTable->timestamp(BaseModel::DELETED_AT)->nullable();
            $objTable->unsignedBigInteger(BaseModel::STAMP_DELETED_BY)->nullable();

            $objTable->index(["participant_id", "user_id"], "idx_participant-id_user-id");
            $objTable->index(["participant_uuid", "user_uuid"], "idx_participant-uuid_user-uuid");
            $objTable->index(["user_id", "participant_id"], "idx_user-id_participant-id");
            $objTable->index(["user_uuid", "participant_uuid"], "idx_user-uuid_participant-uuid");

            $objTable->index(["participant_id", "conference_id"], "idx_participant-id_conference-id");
            $objTable->index(["participant_uuid", "conference_uuid"], "idx_participant-uuid_conference-uuid");
            $objTable->index(["conference_id", "participant_id"], "idx_conference-id_participant-id");
            $objTable->index(["conference_uuid", "participant_uuid"], "idx_conference-uuid_participant-uuid");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("soundblock_conferences_participants");
    }
}
