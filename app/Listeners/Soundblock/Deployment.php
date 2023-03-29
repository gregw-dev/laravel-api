<?php

namespace App\Listeners\Soundblock;

use Mail;
use App\Mail\Soundblock\Deployment as DeploymentMail;
use App\Events\Soundblock\Deployment as CreateDeploymentEvent;

class Deployment
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  CreateDeploymentEvent  $event
     * @return void
     */
    public function handle(CreateDeploymentEvent $event)
    {
        $arrDeploymentMail = [];
        $objUsers = $event->objDeployment->project->team->users;
        $projectUuid = $event->objDeployment->project->project_uuid;

        if(is_object($event->objDeployment)){
            $objTemp = $event->objDeployment->load('platform');
            //transforming into key value array pair
            $arrDeployment[] =  [
                'platform' => $objTemp->platform->name,
                'status'   => $objTemp->deployment_status
            ];
        }else{
            $arrDeployment = $event->objDeployment;
        }

        $arrDeploymentMail[$projectUuid] = $arrDeployment;

        foreach ($objUsers as $objUser) {
            Mail::to($objUser->primary_email->user_auth_email)->send(new DeploymentMail($arrDeploymentMail));
        }

        $event->objDeployment->update([
            "flag_notify_user" => true
        ]);
    }
}
