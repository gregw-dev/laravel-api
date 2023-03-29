<?php

namespace App\Http\Controllers\Soundblock;

use Auth;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Contracts\Soundblock\Contributor\Contributor as ContributorService;
use App\Http\Requests\Soundblock\Contributor\{
    GetContributor,
    StoreContributor,
    UpdateContributor,
    DeleteContributor,
};

/**
 * @group Soundblock
 *
 * Soundblock Contributor
 */
class Contributor extends Controller
{
    /** @var ContributorService */
    private ContributorService $contributorService;

    /**
     * Contributor constructor.
     * @param ContributorService $contributorService
     */
    public function __construct(ContributorService $contributorService){
        $this->contributorService = $contributorService;
    }

    /**
     * @param GetContributor $objRequest
     * @return \Illuminate\Http\Resources\Json\ResourceCollection|Response|object
     */
    public function index(GetContributor $objRequest){
        if ($objRequest->has("contributor")) {
            $objContributor = $this->contributorService->find($objRequest->input("contributor"));

            if (is_null($objContributor)) {
                return ($this->apiReject(null, "Contributor not found.", Response::HTTP_BAD_REQUEST));
            }

            if ($objContributor->account_uuid != $objRequest->input("account")) {
                return ($this->apiReject(null, "Account doesn't have this contributor.", Response::HTTP_FORBIDDEN));
            }

            return ($this->apiReply($objContributor, "", Response::HTTP_OK));
        }

        $objContributors = $this->contributorService->findAllByAccount($objRequest->input("account"));

        return ($this->apiReply($objContributors, "", Response::HTTP_OK));
    }

    /**
     * @param StoreContributor $objRequest
     * @return \Illuminate\Http\Resources\Json\ResourceCollection|Response|object
     */
    public function store(StoreContributor $objRequest){
        $objUser = Auth::user();
        $objAccount = $objUser->accounts()->where("soundblock_accounts.account_uuid", $objRequest->input("account"))->first();
        $objOwnAccount = $objUser->userAccounts()->where("soundblock_accounts.account_uuid", $objRequest->input("account"))->first();
        $strSoundGroup = sprintf("App.Soundblock.Account.%s", $objRequest->input("account"));

        if (
            !is_authorized($objUser, $strSoundGroup, "App.Soundblock.Account.Contributor.Create", "soundblock", true, true) ||
            (is_null($objAccount) && is_null($objOwnAccount))
        ) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        if (is_null($objAccount)) {
            $objAccount = $objOwnAccount;
        }

        $objContributor = $this->contributorService->create($objRequest->only("contributor_name"), $objAccount);

        return ($this->apiReply($objContributor, "Contributor stored successfully.", Response::HTTP_OK));
    }

    /**
     * @param UpdateContributor $objRequest
     * @return \Illuminate\Http\Resources\Json\ResourceCollection|Response|object
     */
    public function update(UpdateContributor $objRequest){
        $objUser = Auth::user();
        $objAccount = $objUser->accounts()->where("soundblock_accounts.account_uuid", $objRequest->input("account"))->first();
        $objOwnAccount = $objUser->userAccounts()->where("soundblock_accounts.account_uuid", $objRequest->input("account"))->first();
        $strSoundGroup = sprintf("App.Soundblock.Account.%s", $objRequest->input("account"));

        if (
            !is_authorized($objUser, $strSoundGroup, "App.Soundblock.Account.Contributor.Update", "soundblock", true, true) ||
            (is_null($objAccount) && is_null($objOwnAccount))
        ) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objContributor = $this->contributorService->find($objRequest->input("contributor"));

        if ($objContributor->account_uuid != $objRequest->input("account")) {
            return ($this->apiReject(null, "Account doesn't have this Contributor.", Response::HTTP_FORBIDDEN));
        }

        if ($objContributor->flag_permanent) {
            $strContributorName = $objContributor->contributor_name;
            return ($this->apiReject(null, $strContributorName." is listed on a track and cannot be updated.", Response::HTTP_FORBIDDEN));
        }

        $boolResult = $this->contributorService->update($objContributor, $objRequest->only("contributor_name"));

        if (!$boolResult) {
            return ($this->apiReject(null, "Something went wrong.", Response::HTTP_BAD_REQUEST));
        }

        return ($this->apiReply(null, "Contributor updated successfully.", Response::HTTP_OK));
    }

    /**
     * @param DeleteContributor $objRequest
     * @return \Illuminate\Http\Resources\Json\ResourceCollection|Response|object
     */
    public function delete(DeleteContributor $objRequest){
        $objUser = Auth::user();
        $objAccount = $objUser->accounts()->where("soundblock_accounts.account_uuid", $objRequest->input("account"))->first();
        $objOwnAccount = $objUser->userAccounts()->where("soundblock_accounts.account_uuid", $objRequest->input("account"))->first();
        $strSoundGroup = sprintf("App.Soundblock.Account.%s", $objRequest->input("account"));

        if (
            !is_authorized($objUser, $strSoundGroup, "App.Soundblock.Account.Contributor.Delete", "soundblock", true, true) ||
            (is_null($objAccount) && is_null($objOwnAccount))
        ) {
            return ($this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN));
        }

        $objContributor = $this->contributorService->find($objRequest->input("contributor"));

        if ($objContributor->account_uuid != $objRequest->input("account")) {
            return ($this->apiReject(null, "Account doesn't have this contributor.", Response::HTTP_FORBIDDEN));
        }

        if(isset($objContributor->tracks) && $objContributor->tracks->count()) {
            return ($this->apiReject(null, "This contributor is associated with a track and it cannot be deleted.", Response::HTTP_FORBIDDEN));
        }

        if ($objContributor->flag_permanent) {
            $strContributorName = $objContributor->contributor_name;
            return ($this->apiReject(null, $strContributorName." is listed on a track and cannot be deleted.", Response::HTTP_FORBIDDEN));
        }

        $boolResult = $this->contributorService->delete($objRequest->input("contributor"));

        if (!$boolResult) {
            return ($this->apiReject(null, "Something went wrong.", Response::HTTP_BAD_REQUEST));
        }

        return ($this->apiReply(null, "Contributor deleted successfully.", Response::HTTP_OK));
    }
}
