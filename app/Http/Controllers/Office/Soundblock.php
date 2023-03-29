<?php

namespace App\Http\Controllers\Office;

use App\Helpers\Util;
use App\Http\Controllers\Controller;
use App\Services\Soundblock\Project;
use Illuminate\Support\Facades\DB;

class Soundblock extends Controller
{

    private Project $projectService;

    public function __construct(Project $projectService)
    {
        $this->projectService = $projectService;
    }

    public function assignMissingSoundblockProjectAndAccountsPermission()
    {
        return $this->projectService->fixSoundblockPermissions();
        // return $this->projectService->testingForProjectsWithoutATeam();
    }
}
