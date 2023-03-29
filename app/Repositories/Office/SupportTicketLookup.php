<?php

namespace App\Repositories\Office;

use App\Models\Support\Ticket\SupportTicketLookup as SupportTicketLookupModel;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\BaseRepository;
use App\Helpers\Util;

class SupportTicketLookup extends BaseRepository
{
    /* @var Model  */
    protected Model $model;

    public function __construct(SupportTicketLookupModel $model)
    {
        $this->model = $model;
    }

    public function insert(Object $objTicket, $strMessageId): SupportTicketLookupModel
    {
        return $this->model->create([
            "row_uuid" => Util::uuid(),
            "ticket_id" => $objTicket->ticket_id,
            "ticket_uuid" => $objTicket->ticket_uuid,
            "lookup_email_ref" => $strMessageId
        ]);
    }

    public function findbyRef($ref) : ?SupportTicketLookupModel
    {
        if(gettype($ref)=="string"){
            return $this->model->where("lookup_email_ref", $ref)->first();
        }else{
            return $this->model->find($ref);
        }
    }
}
