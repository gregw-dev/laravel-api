<?php

namespace App\Http\Controllers\Soundblock;

use Auth;
use App\Models\Users\User;
use Illuminate\Http\Response;
use App\Services\Common\Common;
use App\Http\Controllers\Controller;
use App\Services\Soundblock\Invite as InviteService;

/**
 * @group Soundblock
 *
 * Soundblock routes
 */
class AccountInvite extends Controller {
    /** @var Common */
    private Common $commonService;
    /** @var InviteService */
    private InviteService $inviteService;

    /**
     * AccountInvite constructor.
     * @param Common $commonService
     * @param InviteService $inviteService
     */
    public function __construct(Common $commonService, InviteService $inviteService) {
        $this->commonService = $commonService;
        $this->inviteService = $inviteService;
    }

    public function getInvites() {
        /** @var User $objUser*/
        $objUser = Auth::user();
        [$arrAccountInvites, $arrEmailInvites] = $this->commonService->getAccountInvites($objUser);

        return $this->apiReply(["account" => $arrAccountInvites, "email" => $arrEmailInvites], "", Response::HTTP_OK);
    }

    public function acceptInvite(string $account) {
        /** @var User $objUser*/
        $objUser = Auth::user();
        $objAccount = $this->commonService->find($account);

        if ($objAccount) {
            return $this->apiReply($this->commonService->acceptInvite($objAccount, $objUser));
        }

        return ($this->apiReject(null, "Account not found.", Response::HTTP_BAD_REQUEST));
    }

    public function acceptInviteEmail(string $invite) {
        /** @var User $objUser*/
        $objUser = Auth::user();
        $objInvite = $this->inviteService->find($invite);

        if (empty($objInvite) || $objInvite->invite_email !== $objUser->primary_email->user_auth_email) {
            return ($this->apiReject(null, "Invite not found.", Response::HTTP_BAD_REQUEST));
        }

        if ($objInvite->flag_used) {
            return ($this->apiReject(null, "Invite already used.", Response::HTTP_BAD_REQUEST));
        }

        $objUser = $this->inviteService->useInviteWithoutCreatingUser($objInvite, $objUser);

        return ($this->apiReply($objUser, "Invite used successfully.", Response::HTTP_OK));
    }

    public function rejectInvite(string $account) {
        /** @var User $objUser*/
        $objUser = Auth::user();
        $objAccount = $this->commonService->find($account);

        if ($objAccount) {
            return $this->apiReply($this->commonService->rejectInvite($objAccount, $objUser));
        }

        return ($this->apiReject(null, "Account not found.", Response::HTTP_BAD_REQUEST));
    }

    public function rejectInviteEmail(string $invite) {
        /** @var User $objUser*/
        $objUser = Auth::user();

        $objInvite = $this->inviteService->find($invite);

        if (empty($objInvite) || $objInvite->invite_email !== $objUser->primary_email->user_auth_email) {
            return ($this->apiReject(null, "Invite not found.", Response::HTTP_BAD_REQUEST));
        }

        if ($objInvite->flag_used) {
            return ($this->apiReject(null, "Invite already used.", Response::HTTP_BAD_REQUEST));
        }

        $this->inviteService->rejectEmailInvite($objInvite, $objUser);

        return ($this->apiReply(null, "Invite was rejected.", Response::HTTP_OK));
    }
}
