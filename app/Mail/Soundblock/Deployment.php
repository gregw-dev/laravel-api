<?php

namespace App\Mail\Soundblock;

use App\Models\Core\App;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Repositories\Soundblock\Project as ProjectRepository;

class Deployment extends Mailable{
    use Queueable, SerializesModels;

    private array $arrProjectsDeployments;

    /**
     * Create a new message instance.
     *
     * @param array $arrProjectsDeployments
     */
    public function __construct(array $arrProjectsDeployments) {
        $this->arrProjectsDeployments = $arrProjectsDeployments;
    }

    /**
     * Build the message.
     *
     * @param ProjectRepository $projectRepo
     * @return $this
     */
    public function build(ProjectRepository $projectRepo) {
        $arrAllDeployments = [];
        $this->from(config("constant.email.soundblock.address"), config("constant.email.soundblock.name"));
        $this->subject("Project Deployments Update");
        $this->withSwiftMessage(function ($message) {
            $message->app = App::where("app_name", "soundblock")->first();
        });

        foreach ($this->arrProjectsDeployments as $strProjectUuid => $arrDeployments) {
            $objProject = $projectRepo->find($strProjectUuid);
            usort($arrDeployments, function ($elem1, $elem2) {
                return strcmp($elem1["platform"], $elem2["platform"]);
            });
            $arrAllDeployments[$objProject->project_title] = $arrDeployments;
        }
        $frontendUrl = app_url("soundblock", "http://localhost:4200") . "support";

        return ($this->view('mail.soundblock.deployment')
                    ->with([
                        "link" => $frontendUrl,
                        "project_deployments" => $arrAllDeployments
                    ])
        );
    }
}
