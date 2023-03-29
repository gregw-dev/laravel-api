<?php

namespace App\Http\Controllers\Soundblock;

use Client;
use Builder;
use Exception;
use App\Models\Users\User;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\Soundblock\Projects\Project;
use App\Events\Soundblock\UpdateDeployments;
use App\Jobs\Soundblock\Projects\Deployments\Zip;
use Illuminate\Support\Facades\Auth as AuthFacade;
use App\Http\Transformers\Soundblock\Deployment as DeploymentTransformer;
use App\Contracts\Soundblock\Artist\Artist as ArtistService;
use App\Contracts\Soundblock\Contributor\Contributor as ContributorService;
use App\Http\Requests\{
    Office\Project\Deployment\CreateDeployments,
    Soundblock\Project\Deploy\CreateDeployment,
    Soundblock\Project\Deploy\GetDeployment,
    Soundblock\Project\Deploy\UpdateDeployment
};
use App\Services\{
    Common\App as AppService,
    Soundblock\Project as ProjectService,
    Soundblock\Collection as CollectionService,
    Soundblock\Contracts\Service as ContractService,
    Soundblock\Deployment as DeploymentService,
    Soundblock\Platform as PlatformService
};

/**
 * @group Soundblock
 *
 * Soundblock routes
 */
class Deployment extends Controller
{
    /** @var DeploymentService */
    private DeploymentService $deploymentService;
    /** @var ProjectService */
    private ProjectService $projectService;
    /** @var ContractService */
    private ContractService $objContractService;
    /** @var CollectionService */
    private CollectionService $collectionService;
    /** @var AppService */
    private AppService $appService;
    /** @var ArtistService */
    private ArtistService $artistService;
    /** @var ContributorService */
    private ContributorService $contributorService;
    /** @var PlatformService */
    private PlatformService $platformService;

    /**
     * Deployment constructor.
     * @param DeploymentService $deploymentService
     * @param ProjectService $projectService
     * @param AppService $appService
     * @param ArtistService $artistService
     * @param ContributorService $contributorService
     * @param ContractService $objContractService
     * @param CollectionService $collectionService
     * @param PlatformService $platformService
     */
    public function __construct(DeploymentService $deploymentService, ProjectService $projectService, AppService $appService,
                                ArtistService $artistService, ContributorService $contributorService,
                                ContractService $objContractService, CollectionService $collectionService,
                                PlatformService $platformService) {
        $this->deploymentService = $deploymentService;
        $this->objContractService = $objContractService;
        $this->projectService = $projectService;
        $this->collectionService = $collectionService;
        $this->appService = $appService;
        $this->artistService = $artistService;
        $this->contributorService = $contributorService;
        $this->platformService = $platformService;
    }

    /**
     * @param string $project
     * @param GetDeployment $objRequest
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function index(string $project, GetDeployment $objRequest) {
        if (!$this->projectService->checkUserInProject($project, AuthFacade::user())) {
            return ($this->apiReject(null, "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $arrDeployments = $this->deploymentService->findAllByProject($project, $objRequest->per_page);

        return ($this->paginator($arrDeployments, new DeploymentTransformer(["platform", "status", "collection"])));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param string $project
     * @param CreateDeployment $objRequest
     * @return \Illuminate\Http\Response
     * @throws Exception
     */
    public function store(string $project, CreateDeployment $objRequest) {
        /** @var User $objUser */
        $objUser = AuthFacade::user();
        /** @var Project $objProject*/
        $objProject = $this->projectService->find($project, true);

        $strSoundGroup = sprintf("App.Soundblock.Account.%s", $objProject->account_uuid);

        if (!is_authorized($objUser, $strSoundGroup, "App.Soundblock.Account.Project.Deploy", "soundblock", true, true)) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        if (!$this->objContractService->checkActiveContract($objProject)) {
            return $this->apiReply(null, "Cannot Deploy Project Without Active Contract.", Response::HTTP_FORBIDDEN);
        }

        $arrPlatforms = $this->platformService->findNotDeployedForProject($objProject, "Music", true);

        foreach ($objRequest->input("platforms") as $strPlatformUuid) {
            if (!in_array($strPlatformUuid, $arrPlatforms->pluck("platform_uuid")->toArray())) {
                return $this->apiReply(null, "Some platforms not allowed for deployment.", Response::HTTP_FORBIDDEN);
            }
        }

        if ($objRequest->has("collectionUUID")) {
            $objCollection = $this->collectionService->find($objRequest->input("collectionUUID"), false);
        } else {
            $objCollection = $this->collectionService->findLatestByProject($objProject);
        }

        if (is_null($objCollection)) {
            return $this->apiReject("", "Collection Not Found", 404);
        }

        if ($objProject->flag_project_explicit) {
            $boolCheckExplicit = false;
            foreach ($objCollection->tracks as $objTrack) {
                if ($objTrack->flag_track_explicit) {
                    $boolCheckExplicit = true;
                }
            }

            if (!$boolCheckExplicit) {
                return $this->apiReject("", "Project doesn't have explicit track.", 400);
            }
        }

        $this->projectService->setProjectUPC($objProject);

        if (empty($objProject->project_upc)) {
            return $this->apiReject(null, "Project doesn't have UPC.", 400);
        }

        foreach ($objCollection->tracks as $objTrack) {
            if (empty($objTrack->track_isrc)) {
                $this->collectionService->setTrackIsrc($objTrack);
            }
        }

        if (!$this->collectionService->checkCollectionTracksIsrcs($objCollection)) {
            return $this->apiReject(null, "Collection track doesn't have ISRC.", 400);
        }

        $objDeployment = $this->deploymentService->create($objProject, $objRequest->input("platforms"), $objCollection->collection_uuid);

        $this->deploymentService->setFlagPermanent($objProject);
        $objQueue = $this->projectService->createJob("Job.Soundblock.Project.Deployment.Zip", AuthFacade::user(), Client::app());
        dispatch(new Zip($objCollection, $objQueue));
        event(new UpdateDeployments($objProject));

        $objApp = $this->appService->findOneByName("office");
        $strNotificationArtistName = isset($objProject->artist) ? "by {$objProject->artist->artist_name}" : "";
        $strMemo = "&quot;{$objProject->project_title}&quot; {$strNotificationArtistName} <br>Soundblock &bull; {$objProject->account->account_name}";

        notify_group_permission("Arena.Support", "Arena.Support.Soundblock", $objApp, "Deployment Requested", $strMemo, Builder::notification_link([
            "link_name" => "Check Deployments",
            "url"       => app_url("office") . "soundblock/deployments",
        ]));

        return ($this->apiReply($objDeployment));
    }

    /**
     * @param CreateDeployments $objRequest
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function storeMultiple(CreateDeployments $objRequest) {
        $objUser = AuthFacade::user();

        if (!is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $arrDeployments = $this->deploymentService->createMultiple($objRequest->all());

        return ($this->collection($arrDeployments, new DeploymentTransformer(["status", "platform"])));
    }

    /**
     * @param string $project
     * @param string $deployment
     * @param UpdateDeployment $objRequest
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|Response|object
     * @throws Exception
     */
    public function redeploy(string $project, string $deployment, UpdateDeployment $objRequest){
        $arrUpdate = [];
        $objProject = $this->projectService->find($project, false);

        if (empty($objProject)) {
            return ($this->apiReject(null, "Undefined project.", 400));
        }

        $strSoundGroup = sprintf("App.Soundblock.Account.%s", $objProject->account_uuid);

        if (!is_authorized(AuthFacade::user(), $strSoundGroup, "App.Soundblock.Account.Project.Deploy", "soundblock", true, true)) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objDeployment = $objProject->deployments()->where("deployment_uuid", $deployment)->first();

        if (empty($objDeployment)) {
            return ($this->apiReject(null, "Project doesn't have such deployment.", 400));
        }

        $arrUpdate["collection"] = $objRequest->input("collection");
        $arrUpdate["deployment_status"] = "Redeploy";

        $objDeployment = $this->deploymentService->update($objDeployment, $arrUpdate);
        $this->artistService->unsetFlagPermanentByDeployment($objDeployment);

        $objApp = $this->appService->findOneByName("office");
        $strNotificationArtistName = isset($objProject->artist) ? "by {$objProject->artist->artist_name}" : "";
        $strMemo = "&quot;{$objProject->project_title}&quot; {$strNotificationArtistName} <br>Soundblock &bull; {$objProject->account->account_name}";

        notify_group_permission("Arena.Support", "Arena.Support.Soundblock", $objApp, "Deployment Redeploy Requested", $strMemo, Builder::notification_link([
            "link_name" => "Check Deployments",
            "url"       => app_url("office") . "soundblock/deployments",
        ]));

        return ($this->apiReply($objDeployment, "Deployment updated successfully.", 200));
    }

    public function takedown(string $project, string $deployment){
        $objProject = $this->projectService->find($project, false);

        if (empty($objProject)) {
            return ($this->apiReject(null, "Undefined project.", 400));
        }

        $strSoundGroup = sprintf("App.Soundblock.Account.%s", $objProject->account_uuid);

        if (!is_authorized(AuthFacade::user(), $strSoundGroup, "App.Soundblock.Account.Project.Deploy", "soundblock", true, true)) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objDeployment = $objProject->deployments()->where("deployment_uuid", $deployment)->first();

        if (empty($objDeployment)) {
            return ($this->apiReject(null, "Project doesn't have such deployment.", 400));
        }

//        $objDeployment = $this->deploymentService->update($objDeployment, ["deployment_status" => "Pending takedown"]);
        $objTakedown = $this->deploymentService->createTakedown($objDeployment);
//        $this->artistService->unsetFlagPermanentByDeployment($objDeployment);

        $objApp = $this->appService->findOneByName("office");
        $strNotificationArtistName = isset($objProject->artist) ? "by {$objProject->artist->artist_name}" : "";
        $strMemo = "&quot;{$objProject->project_title}&quot; {$strNotificationArtistName} <br>Soundblock &bull; {$objProject->account->account_name}";

        notify_group_permission("Arena.Support", "Arena.Support.Soundblock", $objApp, "Deployment Takedown Requested", $strMemo, Builder::notification_link([
            "link_name" => "Check Deployments",
            "url"       => app_url("office") . "soundblock/deployments/takedowns",
        ]));

        return ($this->apiReply($objDeployment, "Deployment updated successfully.", 200));
    }
}
