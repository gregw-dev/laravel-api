<?php

namespace App\Models\Soundblock\Payments;

use App\Models\BaseModel;
use App\Models\Users\User;
use App\Models\Soundblock\Platform;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MusicUserBalancePayment extends BaseModel
{
    use HasFactory;

    protected $table = "soundblock_payments_music_users_balance";

    protected $primaryKey = "row_id";

    protected string $uuid = "row_uuid";

    protected $hidden = [
        "row_id", "withdrawal_method_id", "user_id", "project_id", "platform_id",
        BaseModel::DELETED_AT, BaseModel::STAMP_DELETED, BaseModel::STAMP_DELETED_BY,
        BaseModel::UPDATED_AT, BaseModel::STAMP_UPDATED_BY, BaseModel::CREATED_AT, BaseModel::STAMP_CREATED_BY,
    ];

    protected $casts = [
        "withdrawal_method_data" => "array"
    ];

    public function user(): BelongsTo
    {
        return($this->belongsTo(User::class, "user_id", "user_id"));
    }

    public function platform(){
        return($this->belongsTo(Platform::class, "platform_id", "platform_id"));
    }
}
