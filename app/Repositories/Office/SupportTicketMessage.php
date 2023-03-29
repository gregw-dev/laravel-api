<?php

namespace App\Repositories\Office;

use App\Helpers\Util;
use App\Models\Support\Ticket\SupportTicket;
use App\Models\Support\Ticket\SupportTicketMessage as SupportTicketMessageModel;
use App\Repositories\BaseRepository;

class SupportTicketMessage extends BaseRepository {

    public function __construct(SupportTicketMessageModel $objMessage) {
        $this->model = $objMessage;
    }

    public function getMessages(array $arrParams, SupportTicket $ticket, int $perPage = 10, bool $withoutOffice = false){
        $query = $ticket->messages();

        /* Internal messages are only available to Office users. */
        if ($withoutOffice) {
            $query = $query->where("flag_officeonly", false)->orderBy("stamp_created_at", "desc");
        }

        [$query, $availableMetaData] = $this->applyMetaFilters($arrParams, $query);

        $objMessages = $query->with(["user", "attachments"])->orderByDesc("message_id")->paginate($perPage);

        return ([$objMessages, $availableMetaData]);
    }

    public function getUserUnreadMessages(string $user_uuid){
        $unreadUserMessages = $this->model->where("flag_status", "Unread")
            ->where("user_uuid", "!=", $user_uuid)
            ->where("flag_office", 1)
            ->where("flag_officeonly", 0)
            ->whereHas("ticket.supportUser", function ($query) use ($user_uuid){
                $query->where("support_tickets_users.user_uuid", $user_uuid);
            })
            ->whereHas("ticket", function ($query) use ($user_uuid){
                $query->where("support_tickets.flag_status", "!=", "Closed");
            })
            ->get();

        return ($unreadUserMessages);
    }

    public function checkDuplicateMessageText(string $uuidTicket, string $uuidUser, string $messageText){
        return (
            $this->model->where("ticket_uuid", $uuidTicket)
                ->where("user_uuid", $uuidUser)
                ->whereRaw("lower(message_text) = '" . addslashes(Util::lowerLabel($messageText)) . "'")
                ->exists()
        );
    }
}
