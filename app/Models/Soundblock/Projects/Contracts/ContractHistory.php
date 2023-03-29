<?php

namespace App\Models\Soundblock\Projects\Contracts;

use App\Models\BaseModel;

class ContractHistory extends BaseModel
{
    protected $table = "soundblock_projects_contracts_history";

    protected $primaryKey = "row_id";

    protected $casts = [
        "contract_state" => "array",
    ];

    protected $hidden = [
        "remote_host", "remote_addr", "remote_agent"
    ];

    public static function boot() {

        parent::boot();

        static::created(function($item) {
            $item->update([
                "remote_addr" => request()->getClientIp(),
                "remote_host" => gethostbyaddr(request()->getClientIp()),
                "remote_agent" => request()->server("HTTP_USER_AGENT")
            ]);
        });
    }
}
