<?php

namespace App\Services;

use Auth;
use Util;
use App\Repositories\{Accounting\Banking, Accounting\PayMethod};
use App\Models\{Users\User, Users\Accounting\AccountingPayMethods, Users\Accounting\AccountingBanking};
use Symfony\Component\{HttpKernel\Exception\BadRequestHttpException, Routing\Exception\InvalidParameterException};

class Payment {
    /** @var  Banking */
    protected Banking $bankingRepo;
    /** @var PayMethod */
    protected PayMethod $payMethodRepo;

    /**
     * @param Banking $bankingRepo
     * @param PayMethod $payMethodRepo
     * @return void
     */
    public function __construct(Banking $bankingRepo, PayMethod $payMethodRepo) {
        $this->bankingRepo = $bankingRepo;
        $this->payMethodRepo = $payMethodRepo;
    }

    /**
     * @param string $bank
     * @param bool $bnFailure
     * @return AccountingBanking
     * @throws \Exception
     */
    public function findBanking(string $bank, bool $bnFailure = false): AccountingBanking {
        return ($this->bankingRepo->find($bank, $bnFailure));
    }

    /**
     * @param string $payMethod
     * @param bool $bnFailure
     * @return AccountingPayMethods
     * @throws \Exception
     */
    public function findPayMethod(string $payMethod, ?bool $bnFailure = false): AccountingPayMethods {
        return ($this->payMethodRepo->find($payMethod, $bnFailure));
    }

    /**
     * @param User $objUser
     * @param AccountingBanking $objBank
     * @return bool
     */
    public function userHasBankAccount(User $objUser, AccountingBanking $objBank): bool {
        return ($objUser->bankings()->where("row_uuid", $objBank->row_uuid)->exists());
    }

    /**
     * @param User $objUser
     * @param AccountingPayMethods $objPayMethod
     * @return bool
     */
    public function userHasPayMethod(User $objUser, AccountingPayMethods $objPayMethod): bool {
        return ($objUser->paymethods()->where("row_uuid", $objPayMethod->row_uuid)->exists());
    }

    /**
     * @param array $arrParams
     * @param User|null $objUser
     * @return AccountingBanking
     */
    public function createBanking(array $arrParams, ?User $objUser = null): AccountingBanking {
        if (is_null($objUser))
            $objUser = Auth::user();

        $arrBanking = [];
        if (is_null($objUser))
            $objUser = Auth::user();

        $arrBanking["user_id"] = $objUser->user_id;
        $arrBanking["user_uuid"] = $objUser->user_uuid;
        if ($objUser->paymethods->count() == 0 && $objUser->bankings->count() == 0) {
            $arrBanking["flag_primary"] = true;
        } else {
            if (isset($arrParams["flag_primary"]) && $arrParams["flag_primary"]) {
                $this->initForPrimary($objUser);
                $arrBanking["flag_primary"] = true;
            } else {
                $arrBanking["flag_primary"] = false;
            }
        }
        $arrBanking["bank_name"] = $arrParams["bank_name"];
        $arrBanking["account_type"] = Util::ucfLabel($arrParams["account_type"]);
        $arrBanking["account_number"] = $arrParams["account_number"];
        $arrBanking["routing_number"] = $arrParams["routing_number"];

        return ($this->bankingRepo->create($arrBanking));
    }

    /**
     * @param array $arrParams
     * @param User|null $objUser
     * @return AccountingPayMethods
     */
    public function createPayMethod(array $arrParams, ?User $objUser = null): AccountingPayMethods {
        if (is_null($objUser))
            $objUser = Auth::user();

        $arrPayMethod = [];
        $arrPayMethod["user_id"] = $objUser->user_id;
        $arrPayMethod["user_uuid"] = $objUser->user_uuid;
        $arrPayMethod["paymethod_account"] = $arrParams["paymethod_account"];
        $arrPayMethod["paymethod_type"] = $arrParams["paymethod_type"];

        if ($objUser->paymethods->count() == 0 && $objUser->bankings->count() == 0) {
            $arrPayMethod["flag_primary"] = true;
        } else {
            if (isset($arrParams["flag_primary"]) && $arrParams["flag_primary"]) {
                $this->initForPrimary($objUser);
            } else {
                $arrPayMethod["flag_primary"] = false;
            }
        }

        return ($this->payMethodRepo->create($arrPayMethod));
    }

    /**
     * @param User $objUser
     * @return bool
     */
    public function initForPrimary(User $objUser): bool {
        return ($this->initBankingForPrimary($objUser) && $this->initPayMethodForPrimary($objUser));
    }

    /**
     * @param User $objUser
     * @return bool
     */
    public function initBankingForPrimary(User $objUser) {
        $arrObjBankings = $objUser->bankings;

        $arrObjBankings->transform(function ($objBanking) {
            $objBanking->update(["flag_primary" => false]);
        });

        return (true);
    }

    /**
     * @param User $objUser
     * @return bool
     */
    public function initPayMethodForPrimary(User $objUser) {

        $arrObjPayMethods = $objUser->paymethods;
        $arrObjPayMethods->transform(function ($objPayMethod) {
            $objPayMethod->update(["flag_primary" => false]);
        });

        return (true);
    }

    /**
     * @param array $arrParams
     * @param User $objUser
     * @return mixed
     * @throws \Exception
     */
    public function setPrimary(array $arrParams, User $objUser) {
        if (Util::lowerLabel($arrParams["type"]) === "bank") {
            return ($this->setPrimaryBanking($arrParams, $objUser));
        } else if (Util::lowerLabel($arrParams["type"]) === "paymethod") {
            return ($this->setPrimaryPayMethod($arrParams, $objUser));
        } else {
            throw new InvalidParameterException();
        }
    }

    /**
     * @param array $arrParams
     * @param User $objUser
     * @return AccountingBanking
     * @throws \Exception
     */
    public function setPrimaryBanking(array $arrParams, User $objUser): AccountingBanking {
        $objBanking = $this->findBanking($arrParams["bank"], true);
        if (!$this->userHasBankAccount($objUser, $objBanking))
            abort(400, "The user has not this bank account.");
        $arrBanking = [];
        if ($arrParams["flag_primary"]) {
            $this->initForPrimary($objBanking->user);
            $arrBanking["flag_primary"] = true;

            return ($this->bankingRepo->update($objBanking, $arrBanking));
        } else {
            throw new InvalidParameterException();
        }
    }

    /**
     * @param array $arrParams
     * @param User $objUser
     * @return AccountingPayMethods
     * @throws \Exception
     */
    public function setPrimaryPayMethod(array $arrParams, User $objUser): AccountingPayMethods {
        $objPayMethod = $this->findPayMethod($arrParams["paymethod"], true);
        if (!$this->userHasPayMethod($objUser, $objPayMethod))
            abort(400, "The user has not this pay method.");
        $arrPayMethod = [];
        if ($arrParams["flag_primary"]) {
            $this->initForPrimary($objPayMethod->user);
            $arrPayMethod["flag_primary"] = true;

            return ($this->payMethodRepo->update($objPayMethod, $arrPayMethod));
        } else {
            throw new InvalidParameterException();
        }
    }

    /**
     * @param AccountingBanking $objBanking
     * @param User $objUser
     * @param array $arrParams
     * @return AccountingBanking
     */
    public function updateBanking(AccountingBanking $objBanking, User $objUser, array $arrParams): AccountingBanking {
        $arrBanking = [];
        if (!$this->userHasBankAccount($objUser, $objBanking))
            throw new BadRequestHttpException("User hasn't this account.");

        if (isset($arrParams["flag_primary"]) && $arrParams["flag_primary"]) {
            $this->initForPrimary($objUser);
            $arrBanking["flag_primary"] = true;
        } else {
            $arrBanking["flag_primary"] = false;
        }

        if (isset($arrParams["bank_name"]))
            $arrBanking["bank_name"] = $arrParams["bank_name"];

        if (isset($arrParams["account_type"]))
            $arrBanking["account_type"] = Util::ucfLabel($arrParams["account_type"]);

        if (isset($arrParams["account_number"]))
            $arrBanking["account_number"] = $arrParams["account_number"];

        if (isset($arrParams["routing_number"]))
            $arrBanking["routing_number"] = $arrParams["routing_number"];

        return ($this->bankingRepo->update($objBanking, $arrBanking));
    }

    /**
     * @param AccountingPayMethods $objPayMethod
     * @param User $objUser
     * @param array $arrParams
     * @return AccountingPayMethods
     */
    public function updatePayMethod(AccountingPayMethods $objPayMethod, User $objUser, array $arrParams): AccountingPayMethods {
        $arrPayMethod = [];

        if (isset($arrParams["paymethod_account"]))
            $arrPayMethod["paymethod_account"] = $arrParams["paymethod_account"];

        if (isset($arrParams["flag_primary"]) && $arrParams["flag_primary"]) {
            $this->initForPrimary($objUser);
            $arrPayMethod["flag_primary"] = true;
        } else {
            $arrPayMethod["flag_primary"] = false;
        }

        return ($this->payMethodRepo->update($objPayMethod, $arrPayMethod));
    }

    /**
     * @param string $payMethod
     * @param User|null $objUser
     * @return bool
     * @throws \Exception
     */
    public function deletePayMethod(string $payMethod, User $objUser = null) {
        if (is_null($objUser))
            $objUser = Auth::user();

        $objPayMethod = $this->findPayMethod($payMethod, true);
        if ($this->userHasPayMethod($objUser, $objPayMethod)) {
            return ($objPayMethod->delete());
        } else {
            throw new BadRequestHttpException("User has n't this pay method.");
        }
    }

    /**
     * @param string $bank
     * @param User|null $objUser
     * @return bool
     * @throws \Exception
     */
    public function deleteBanking(string $bank, ?User $objUser = null): bool {
        if (is_null($objUser))
            $objUser = Auth::user();

        $objBanking = $this->findBanking($bank, true);
        if ($this->userHasBankAccount($objUser, $objBanking)) {
            return ($objBanking->delete());
        } else {
            abort(400, "The user has n't this bank account.");
        }
    }
}
