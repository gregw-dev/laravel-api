<?php

namespace App\Http\Controllers\Account;

use Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Util;
use Auth;
use Exception;
use App\Models\Users\User;
use App\Services\{
    Auth as AuthService,
    Email,
    Payment,
    Phone,
    Postal,
    User as UserService
};
use App\Services\Core\Auth\AuthPermission;
use App\Http\Requests\User\Profile\{GetAddress,
    GetBankAccount,
    GetEmails,
    GetPayMethods,
    GetPhones,
    UpdateBankAccount,
    UpdateEmail,
    UpdatePayMethod,
    UpdatePhone,
    UpdatePostal
};
use App\Http\Requests\Soundblock\Profile\{AddBankAccount,
    AddEmail,
    AddPayMethod,
    AddPhone,
    AddPostal,
    DeletePhone,
    DeleteBankAccount,
    DeleteEmail,
    DeletePayMethod,
    DeletePostal,
    SetPrimary,
    UpdateName
};
use App\Http\Requests\Soundblock\Bootloader\User\GetUser;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @group Account
 *
 */
class Profile extends Controller {
    /** @var AuthService $authService */
    protected AuthService $authService;
    /** @var AuthPermission $authPermService */
    protected AuthPermission $authPermService;
    /** @var UserService */
    private UserService $userService;

    /**
     * @param AuthService $authService
     * @param AuthPermission $authPermService
     * @param UserService $userService
     */
    public function __construct(AuthService $authService, AuthPermission $authPermService, UserService $userService) {
        $this->authService = $authService;
        $this->authPermService = $authPermService;
        $this->userService = $userService;
    }

    /**
     * @return object
     * @throws Exception
     */
    public function index() {
        try {
            /** @var User */
            $objUser = Auth::user();

            $objUser->load(["emails", "aliases", "phones", "postals", "paymethods", "bankings"])
                ->setAppends(["name", "avatar"])->makeVisible(["name_first", "name_middle", "name_last"]);

            return ($this->apiReply($objUser));
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param GetUser $objRequest
     * @return object
     * @throws Exception
     */
    public function show(GetUser $objRequest) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            [$user, $arrUserMeta] = $this->userService->findForOffice($objRequest->user);

            $isAuthorize = is_authorized($objUser, "Arena.Support", "Arena.Support.Soundblock.SignInAs");

            if ($isAuthorize && $objUser->user_id != $user->user_id && $objUser->userAccounts->count() > 0) {
                $user->auth_soundblock = route("sign.in.as", ["user_uuid" => $user->user_uuid]);
            }

            $objUser = $user;
            $objUser->meta = $arrUserMeta;
        }

        $objUser->load(["emails", "aliases", "phones", "postals", "paymethods", "bankings"])
                ->setAppends(["name", "avatar"])->makeVisible(["name_first", "name_middle", "name_last"]);

        return ($this->apiReply($objUser));
    }

    /**
     * @param GetPhones $objRequest
     * @param Phone $phoneService
     * @return \Illuminate\Http\Response|object
     * @throws Exception
     */
    public function getPhones(GetPhones $objRequest, Phone $phoneService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user);
        }

        $arrPhones = $phoneService->findByUser($objUser, $objRequest->per_page);

        return ($this->apiReply($arrPhones, "", Response::HTTP_OK));
    }

    /**
     * @param AddPhone $objRequest
     * @param Phone $phoneService
     * @return \Illuminate\Http\Response|object
     * @throws Exception
     */
    public function storePhone(AddPhone $objRequest, Phone $phoneService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $phone = $phoneService->create($objRequest->all(), $objUser);

        return ($this->apiReply($phone, "Added new phone", 201));
    }

    /**
     * @param UpdatePhone $objRequest
     * @param Phone $phoneService
     * @return \Illuminate\Http\Response|object
     * @throws Exception
     */
    public function updatePhone(UpdatePhone $objRequest, Phone $phoneService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->input("user"));
        }

        $phone = $phoneService->find($objRequest->input("old_phone_number"), $objUser);

        if (!$phone) {
            throw new BadRequestHttpException("User Hasn't This Phone");
        }

        $phone = $phoneService->update($phone, $objUser, $objRequest->all());

        return ($this->apiReply($phone));
    }

    /**
     * @param DeletePhone $objRequest
     * @param Phone $phoneService
     * @return \Illuminate\Http\Response|object
     * @throws Exception
     */
    public function deletePhone(DeletePhone $objRequest, Phone $phoneService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user);
        }

        $phoneService->delete($objRequest->phone_number, $objUser);

        return ($this->apiReply(null, "Deleted phone"));
    }

    /**
     * @param GetAddress $objRequest
     * @param Postal $postalService
     * @return \Illuminate\Http\Response|object
     * @throws Exception
     */
    public function getPostals(GetAddress $objRequest, Postal $postalService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $arrPostals = $postalService->findByUser($objUser, $objRequest->per_page);

        return ($this->apiReply($arrPostals, "", Response::HTTP_OK));

    }

    /**
     * @param AddPostal $objRequest
     * @param Postal $postalService
     * @return object
     * @throws Exception
     */
    public function storePostal(AddPostal $objRequest, Postal $postalService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $objPostal = $postalService->create($objRequest->all(), $objUser);

        return ($this->apiReply($objPostal));
    }

    /**
     * @param UpdatePostal $objRequest
     * @param Postal $postalService
     * @return object
     * @throws Exception
     */
    public function updatePostal(UpdatePostal $objRequest, Postal $postalService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $objPostal = $postalService->find($objRequest->postal, true);
        $objPostal = $postalService->update($objPostal, $objUser, $objRequest->all());

        return ($this->apiReply($objPostal));
    }

    /**
     * @param DeletePostal $objRequest
     * @param Postal $postalService
     * @return object
     * @throws Exception
     */
    public function deletePostal(DeletePostal $objRequest, Postal $postalService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        if ($postalService->delete($objRequest->postal, $objUser)) {
            return ($this->apiReply());
        } else {
            return ($this->apiReject());
        }
    }

    /**
     * @param GetEmails $objRequest
     * @param Email $emailService
     * @return object
     * @throws Exception
     */
    public function getEmails(GetEmails $objRequest, Email $emailService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $arrEmails = $emailService->findByUser($objUser, $objRequest->per_page);

        return ($this->apiReply($arrEmails, "", Response::HTTP_OK));
    }

    /**
     * @param AddEmail $objRequest
     * @param Email $emailService
     * @return object
     * @throws Exception
     */
    public function storeEmail(AddEmail $objRequest, Email $emailService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $objEmail = $emailService->create($objRequest->input("user_auth_email"), $objUser);
        $objEmail = $emailService->sendVerificationEmail($objEmail);

        return ($this->apiReply($objEmail));
    }

    /**
     * @param UpdateEmail $objRequest
     * @param Email $emailService
     * @return object
     * @throws Exception
     */
    public function updateEmail(UpdateEmail $objRequest, Email $emailService) {
        $objEmail = $emailService->find($objRequest->old_user_auth_email, true);
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $objEmail = $emailService->update($objEmail, $objUser, $objRequest->all());

        return ($this->apiReply($objEmail));
    }

    /**
     * @param DeleteEmail $objRequest
     * @param Email $emailService
     * @return object
     * @throws Exception
     */
    public function deleteEmail(DeleteEmail $objRequest, Email $emailService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $objEmail = $emailService->find($objRequest->user_auth_email);

        if (!$emailService->userHasEmail($objUser, $objEmail))
            abort(400, "The User Doesn't Have Current Email.");
        if ($emailService->delete($objRequest->user_auth_email, $objUser)) {
            return ($this->apiReply());
        }
    }

    /**
     * @param UpdateName $objRequest
     * @return object
     * @throws Exception
     */
    public function updateName(UpdateName $objRequest) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $arrName = Util::parse_name($objRequest->name);
        $objUser = $this->userService->update($objUser, $arrName);

        return ($this->apiReply($objUser));
    }

    /**
     * @param GetBankAccount $objRequest
     * @return object
     * @throws Exception
     */
    public function getBankings(GetBankAccount $objRequest) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $arrBankAccs = $objUser->bankings;

        return ($this->apiReply($arrBankAccs));
    }

    /**
     * @param AddBankAccount $objRequest
     * @param Payment $paymentService
     * @return object
     * @throws Exception
     */
    public function storeBanking(AddBankAccount $objRequest, Payment $paymentService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $objBank = $paymentService->createBanking($objRequest->all(), $objUser);

        return ($this->apiReply($objBank));
    }

    /**
     * @param UpdateBankAccount $objRequest
     * @param Payment $paymentService
     * @return object
     * @throws Exception
     */
    public function updateBanking(UpdateBankAccount $objRequest, Payment $paymentService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $objBanking = $paymentService->findBanking($objRequest->bank, true);
        $objBanking = $paymentService->updateBanking($objBanking, $objUser, $objRequest->all());

        return ($this->apiReply($objBanking));
    }

    /**
     * @param SetPrimary $objRequest
     * @param Payment $paymentService
     * @return object
     * @throws Exception
     */
    public function setPrimary(SetPrimary $objRequest, Payment $paymentService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $object = $paymentService->setPrimary($objRequest->all(), $objUser);

        return ($this->apiReply($object));
    }

    /**
     * @param DeleteBankAccount $objRequest
     * @param Payment $paymentService
     * @return object
     * @throws Exception
     */
    public function deleteBankAccount(DeleteBankAccount $objRequest, Payment $paymentService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $paymentService->deleteBanking($objRequest->bank, $objUser);

        return ($this->apiReply(null, "Deleted bank account"));
    }

    /**
     * @param GetPayMethods $objRequest
     * @return object
     * @throws Exception
     */
    public function getPayMethods(GetPayMethods $objRequest) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        return ($this->apiReply($objUser->paymethods()->orderBy("flag_primary", "desc")->get()));
    }

    /**
     * @param AddPayMethod $objRequest
     * @param Payment $paymentService
     * @return object
     * @throws Exception
     */
    public function storePayMethod(AddPayMethod $objRequest, Payment $paymentService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $payMethod = $paymentService->createPayMethod($objRequest->all(), $objUser);

        return ($this->apiReply($payMethod));
    }

    /**
     * @param UpdatePayMethod $objRequest
     * @param Payment $paymentService
     * @return object
     * @throws Exception
     */
    public function updatePayMethod(UpdatePayMethod $objRequest, Payment $paymentService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $objPayaMethod = $paymentService->findPayMethod($objRequest->paymethod, true);
        $objPayaMethod = $paymentService->updatePayMethod($objPayaMethod, $objUser, $objRequest->all());

        return ($this->apiReply($objPayaMethod));
    }

    /**
     * @param DeletePayMethod $objRequest
     * @param Payment $paymentService
     * @return object
     * @throws Exception
     */
    public function deletePayMethod(DeletePayMethod $objRequest, Payment $paymentService) {
        /** @var User $objUser */
        $objUser = Auth::user();

        if ($objRequest->has("user") && Client::app()->app_name == "office" &&
            is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            $objUser = $this->userService->find($objRequest->user, true);
        }

        $paymentService->deletePayMethod($objRequest->paymethod, $objUser);

        return ($this->apiReply(null, "Deleted pay method."));
    }
}
