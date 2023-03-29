<?php

namespace App\Models\Soundblock\Payments;

use App\Models\BaseModel;
use App\Models\Soundblock\Platform;
use App\Models\Soundblock\Projects\Project;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MusicUserPayment extends BaseModel
{
    use HasFactory;

    protected $table = "soundblock_payments_music_users";

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    protected $hidden = [
        "row_id", "project_id", "user_id",
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
        BaseModel::UPDATED_AT, BaseModel::STAMP_UPDATED_BY, BaseModel::CREATED_AT, BaseModel::STAMP_CREATED_BY,
    ];

    public function user(): BelongsTo
    {
        return($this->belongsTo(User::class, "user_id", "user_id"));
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, "platform_id","platform_id");
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, "project_id","project_id");
    }
}
