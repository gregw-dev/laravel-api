<?php

namespace App\Console\Commands\Soundblock;

use Illuminate\Console\Command;
use App\Services\Soundblock\Project;
use App\Models\Soundblock\Projects\Project as ProjectModel;
use App\Models\Soundblock\Data\ProjectsRole;

class AssignTeamToProjectsWithoutTeam extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soundblock:fix_teams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command assigns project account owner as the first team member of a project';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    private Project $projectService;

    public function __construct(Project $projectService)
    {
        parent::__construct();
        $this->projectService = $projectService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return $this->fixTeams();
    }

    public function fixTeams()
    {
        set_time_limit(0);
        $objAllProject = ProjectModel::all();
        $arrNewTeams = [];
        $this->withProgressBar($objAllProject, function ($objProject) use (&$arrNewTeams) {
            if (!$objProject->team) {

                $objAccountOwner = $objProject->account->user;
                $objTeam = $this->projectService->teamService->create($objProject);
                $objProjectRole = ProjectsRole::where("data_role", "Owner")->first();
                $this->projectService->teamService->storeMember([
                    "user_uuid" => $objAccountOwner->user_uuid,
                    "team" => $objTeam->team_uuid,
                    "role_id" => $objProjectRole->data_id,
                    "role_uuid" => $objProjectRole->data_uuid,
                ]);
                array_push($arrNewTeams, $objTeam);
            }
        });
        return $this->info(count($arrNewTeams). " new teams added successfully");
    }
}
