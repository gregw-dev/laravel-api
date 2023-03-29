<?php

namespace App\Http\Controllers\Soundblock;

use Auth;
use Exception;
use App\Models\Users\User;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Soundblock\Project\Draft\SaveDraft;
use App\Services\Soundblock\ProjectDraft as ProjectDraftService;
use App\Http\Transformers\Soundblock\ProjectDraft as ProjectDraftTransformer;

/**
 * @group Soundblock
 *
 * Soundblock routes
 */
class ProjectDraft extends Controller {
    private ProjectDraftService $draftService;

    /**
     * @param ProjectDraftService $draftService
     */
    public function __construct(ProjectDraftService $draftService) {
        $this->draftService = $draftService;
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|Response|object
     * @throws Exception
     */
    public function index() {
        $arrDrafts = $this->draftService->findAllByUser(Auth::user());

        return ($this->apiReply($arrDrafts, "", 200));
    }

    /**
     * @param SaveDraft $objRequest
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function store(SaveDraft $objRequest) {
        /** @var User $objUser */
        $objUser = Auth::user();
        $strGroupName = sprintf("App.Soundblock.Account.%s", $objRequest->account);

        if (!is_authorized($objUser, $strGroupName, "App.Soundblock.Account.Project.Create", "soundblock", true, true)) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        if (isset($objRequest->draft)) {
            $objDraft = $this->draftService->find($objRequest->draft);
            $objDraft = $this->draftService->putJsonField($objDraft, $objRequest->all());
        } else {
            $objDraft = $this->draftService->create($objRequest->all());
        }

        return ($this->item($objDraft, new ProjectDraftTransformer(["account"])));
    }

    /**
     * @param string $draft_uuid
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Resources\Json\ResourceCollection|Response|object
     * @throws Exception
     */
    public function delete(string $draft_uuid){
        $objUser = Auth::user();
        $objDraft = $this->draftService->find($draft_uuid, true);

        $strGroupName = sprintf("App.Soundblock.Account.%s", $objDraft->account);

        if (!is_authorized($objUser, $strGroupName, "App.Soundblock.Account.Project.Create", "soundblock", true, true)) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $boolResult = $this->draftService->softDelete($objDraft);

        if ($boolResult) {
            return ($this->apiReply(null, "Draft deleted.", Response::HTTP_OK));
        }

        return ($this->apiReject(null, "Draft wasn't deleted.", Response::HTTP_BAD_REQUEST));
    }
}
