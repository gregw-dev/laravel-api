<?php

namespace App\Console\Commands\Soundblock;

use Illuminate\Console\Command;
use App\Services\Soundblock\Conference;

class DestroyInactiveConferenceRooms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soundblock:destroy_inactive_conference_rooms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Conference $conferenceService)
    {
        return $conferenceService->destroyRoomWithInactiveParticipant();
    }
}
