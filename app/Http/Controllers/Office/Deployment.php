<?php

namespace App\Http\Controllers\Office;

use App\Jobs\Soundblock\Projects\Deployments\Zip;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Client;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Helpers\Filesystem\Soundblock;
use App\Contracts\Soundblock\Artist\Artist as ArtistContract;
use App\Services\{
    Soundblock\Project as ProjectService,
    Soundblock\Deployment as DeploymentService,
    Soundblock\Collection as CollectionService,
    Soundblock\Contracts\Service as ContractService
};
use App\Http\Requests\{Office\Project\Deployment\GetAllDeploymentsTakedowns,
    Office\Project\Deployment\GetDeployments,
    Office\Project\Deployment\ReUploadDeploymentFiles,
    Office\Project\Deployment\TakedownDeployment,
    Office\Project\Deployment\UpdateDeployment,
    Office\Project\Deployment\GetAllDeployments,
    Soundblock\Project\Deploy\CreateDeployment};

/**
 * @group Office Soundblock
 *
 */
class Deployment extends Controller
{
    /** @var DeploymentService */
    private DeploymentService $deploymentService;
    /** @var ProjectService */
    private ProjectService $projectService;
    /** @var ContractService */
    private ContractService $contractService;
    private CollectionService $collectionService;
    /** @var ArtistContract */
    private ArtistContract $artistService;

    /**
     * Deployment constructor.
     * @param DeploymentService $deploymentService
     * @param ProjectService $projectService
     * @param ContractService $contractService
     * @param CollectionService $collectionService
     * @param ArtistContract $artistService
     */
    public function __construct(DeploymentService $deploymentService, ProjectService $projectService,
                                ContractService $contractService, CollectionService $collectionService,
                                ArtistContract $artistService) {
        $this->deploymentService = $deploymentService;
        $this->projectService    = $projectService;
        $this->contractService   = $contractService;
        $this->collectionService = $collectionService;
        $this->artistService     = $artistService;
    }

    /**
     * @param GetDeployments $objRequest
     * @param string $project
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|Response|object
     * @throws \Exception
     */
    public function index(GetDeployments $objRequest, string $project) {
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $perPage = $objRequest->input("per_page", 10);
        $arrDeployments = $this->deploymentService->findProjectDeployments($project, $perPage);

        return ($this->apiReply($arrDeployments, "", Response::HTTP_OK));
    }

    /**
     * @param GetAllDeployments $objRequest
     * @return \Illuminate\Contracts\Routing\ResponseFactory|Response|object
     * @throws \Exception
     */
    public function getAllDeployments(GetAllDeployments $objRequest){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $arrSort = $objRequest->only(["sort_project", "sort_created_at"]);
        [$objDeployments, $availableMetaData] = $this->deploymentService->findAll($objRequest->all(), $arrSort, true);
        $availableMetaData["pending_deployments"] = $objDeployments->total();

        return ($this->apiReply($objDeployments, "", Response::HTTP_OK, $availableMetaData));
    }

    /**
     * @param string $deployment
     * @return \Illuminate\Contracts\Routing\ResponseFactory|Response|object
     * @throws \Exception
     */
    public function getDeploymentDetails(string $deployment){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $deploymentInfo = $this->deploymentService->getDeploymentInfo($deployment);

        return ($this->apiReply($deploymentInfo, "", 200));
    }

    public function downloadZip(string $deployment) {
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objDeployment = $this->deploymentService->find($deployment, false);

        if (is_null($objDeployment)) {
            return $this->apiReject("", "Deployment Not Found.", 404);
        }

        $objCollection = $objDeployment->collection;
        $objBucket = bucket_storage("office");

        if (!$objBucket->exists("public/" . Soundblock::office_deployment_project_zip_path($objCollection))) {
            return $this->apiReject("", "Deployment Zip Not Found.", 404);
        }

        $strDownloadLink = cloud_url("office") . Soundblock::office_deployment_project_zip_path($objCollection);

        return ($this->apiReply(["download_link" => $strDownloadLink],"",Response::HTTP_OK));
    }

    public function getDeploymentsByCollection(string $collection) {
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objCollection = $this->collectionService->find($collection);

        if (is_null($objCollection)) {
            return $this->apiReject(null, "Collection Not Found.", Response::HTTP_NOT_FOUND);
        }

        $arrDeploymentInfo = $this->deploymentService->getCollectionDeployments($objCollection);

        return $this->apiReply($arrDeploymentInfo);
    }

    public function getTakedowns(GetAllDeploymentsTakedowns $objRequest){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $perPage = $objRequest->has("per_page") ? $objRequest->input("per_page") : 10;

        $objDeploymentsTakedowns = $this->deploymentService->getPendingTakedowns($perPage);

        return ($this->apiReply($objDeploymentsTakedowns, "", Response::HTTP_OK));
    }

    /**
     * @param string $project
     * @param CreateDeployment $objRequest
     * @return \Illuminate\Contracts\Routing\ResponseFactory|Response|object
     * @throws \Exception
     */
    public function store(string $project, CreateDeployment $objRequest) {
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $project = $this->projectService->find($project, true);

        if (!$this->contractService->checkActiveContract($project)) {
            return $this->apiReply(null, "Cannot Deploy Project Without Active Contract.", Response::HTTP_FORBIDDEN);
        }

        $objDeployment = $this->deploymentService->create($project, $objRequest->input("platforms"), $objRequest->input("collection"));

        return ($this->apiReply($objDeployment));
    }

    public function takedown(TakedownDeployment $objRequest){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $boolResult = $this->deploymentService->updateTakedown($objRequest->input("takedown"));

        if (!$boolResult) {
            return ($this->apiReject(null, "deployment hasn't updated.", Response::HTTP_BAD_REQUEST));
        }

        return ($this->apiReply(null, "Deployment updated successfully.", Response::HTTP_OK));
    }

    /**
     * @param UpdateDeployment $objRequest
     * @param string $deployment
     * @return Deployment|\Illuminate\Contracts\Routing\ResponseFactory|Response|object
     * @throws \Exception
     */
    public function updateDeployment(UpdateDeployment $objRequest, string $deployment) {
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objDeployment = $this->deploymentService->find($deployment);

        if (!in_array($objDeployment->deployment_status, ["Pending takedown", "Pending"])) {
            return ($this->apiReject(null, "Deployment not allowed for updating.", Response::HTTP_FORBIDDEN));
        }

        $objDeployment = $this->deploymentService->update($objDeployment, $objRequest->all());
        $this->artistService->unsetFlagPermanentByDeployment($objDeployment);
        $this->projectService->updateFlagMusicPermanent($objDeployment->project);

        return ($this->apiReply($objDeployment, "Deployment has been updated successfully.", 200));
    }

    public function updateCollectionDeployments(string $collection, UpdateDeployment $objRequest) {
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Soundblock.Deploy", "office")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }
        $objCollection = $this->collectionService->find($collection);

        if (is_null($objCollection)) {
            return $this->apiReject(null, "Collection Not Found.", Response::HTTP_NOT_FOUND);
        }

        $boolResult = $this->deploymentService->updateCollectionDeployments($objCollection, $objRequest->all());

        if (!$boolResult) {
            return ($this->apiReject(null, "Something went wrong.", Response::HTTP_BAD_REQUEST));
        }

        $arrDeploymentInfo = $this->deploymentService->getCollectionDeployments($objCollection);

        return ($this->apiReply($arrDeploymentInfo));
    }

    public function reUploadFiles(ReUploadDeploymentFiles $objRequest){
        if (!is_authorized(Auth::user(), "Arena.Superusers", "Arena.Developers.Default")) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objCollection = $this->collectionService->find($objRequest->input("collection"));

        $objQueue = $this->projectService->createJob("Job.Soundblock.Project.Deployment.Zip.Admin", Auth::user(), Client::app());
        dispatch(new Zip($objCollection, $objQueue));

        return ($this->apiReply(null, "Re-upload job dispatched successfully.", Response::HTTP_OK));
    }
}
