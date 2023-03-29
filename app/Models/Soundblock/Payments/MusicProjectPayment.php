<?php

namespace App\Models\Soundblock\Payments;

use App\Models\BaseModel;
use App\Models\Soundblock\Platform;
use App\Models\Soundblock\Projects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MusicProjectPayment extends BaseModel
{
    use HasFactory,SoftDeletes;

    protected $table = "soundblock_payments_music_projects";

    protected $primaryKey = "payment_id";

    protected string $uuid = "payment_uuid";

    protected $guarded = [];

    protected $hidden = [
        "payment_id", "account_id", "platform_id",
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
        BaseModel::UPDATED_AT, BaseModel::STAMP_UPDATED_BY, BaseModel::CREATED_AT, BaseModel::STAMP_CREATED_BY,
    ];

    public function project(): BelongsTo
    {
        return($this->belongsTo(Project::class, "project_id", "project_id"));
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class, "platform_id","platform_id");
    }

}
