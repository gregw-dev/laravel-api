<?php

namespace App\Repositories\Soundblock\Payments;

use App\Models\BaseModel;
use App\Models\Soundblock\Platform;
use App\Models\Users\User;
use App\Repositories\BaseRepository;
use App\Models\Soundblock\Payments\MusicUserBalancePayment as MusicUserBalancePaymentModel;
use Util;
use App\Repositories\Soundblock\Platform as PlatformRepository;

class MusicUserBalancePayment extends BaseRepository {
    /** @var PlatformRepository */
    private PlatformRepository $platformRepo;

    /**
     * ReportProjectUser constructor.
     * @param MusicUserBalancePaymentModel $objMusicUserBalancePayment
     * @param PlatformRepository $platformRepo
     */
    public function __construct(MusicUserBalancePaymentModel $objMusicUserBalancePayment, PlatformRepository $platformRepo) {
        $this->model = $objMusicUserBalancePayment;
        $this->platformRepo = $platformRepo;
    }

    /**
     * @param string $strUserUuid
     * @return mixed
     */
    public function findCurrentByUser(string $strUserUuid){
        return ($this->model->where("user_uuid", $strUserUuid)->latest("row_id")->first());
    }

    public function findAllByUser(string $strUserUuid, int $perPage){
        return ($this->model->where("user_uuid", $strUserUuid)->orderBy("row_id", "desc")->paginate($perPage));
    }

    /**
     * @param string $strUserUuid
     * @param int $perPage
     * @return mixed
     */
    public function findUserEarnedHistory(string $strUserUuid, int $perPage){
        return ($this->model->where("user_uuid", $strUserUuid)->whereNull("withdrawal_method")->where("platform_id", "!=", 0)->orderBy("row_id", "desc")->paginate($perPage));
    }

    /**
     * @param string $strUserUuid
     * @param string $strYear
     * @param string $strMonth
     * @return mixed
     */
    public function findUserEarnedHistoryByDates(string $strUserUuid, string $strYear, string $strMonth){
        return (
            $this->model->where("user_uuid", $strUserUuid)
                ->whereNull("withdrawal_method")
                ->where("platform_id", "!=", 0)
                ->whereYear("date_starts", $strYear)
                ->whereMonth("date_ends", $strMonth)
                ->orderBy("row_id", "desc")
                ->get()
        );
    }

    /**
     * @param string $strUserUuid
     * @param int $perPage
     * @return mixed
     */
    public function findUserWithdrawalHistory(string $strUserUuid, int $perPage){
        return ($this->model->where("user_uuid", $strUserUuid)->whereNotNull("withdrawal_method")->orderBy("row_id", "desc")->paginate($perPage));
    }

    /**
     * @param array $arrData
     * @param $objProject
     * @param string $dateStarts
     * @param string $dateEnds
     * @param string|null $strPaymentMemo
     * @return bool
     * @throws \Exception
     */
    public function createFromReports(array $arrData, $objProject, string $dateStarts, string $dateEnds, string $strPaymentMemo = null){
        $objApplePlatform = $this->platformRepo->findByName("Apple Music");
        foreach ($arrData as $intUserId => $arrPlatformRevenueData) {
            foreach ($arrPlatformRevenueData as $intPlatformId => $floatAmount) {
                $objUser = User::find($intUserId);
                $objPlatform = $this->platformRepo->find($intPlatformId);
                $objCurrentBalance = $this->findCurrentByUser($objUser->user_uuid);
                $floatUserBalance = $floatAmount;

                if (!is_null($objCurrentBalance)) {
                    $floatUserBalance = $objCurrentBalance->user_balance + $floatAmount;
                }

                $strMemo = $objPlatform->name;

                if ($objApplePlatform->platform_id == $objPlatform->platform_id && !empty($strPaymentMemo)) {
                    $strMemo = $objPlatform->name . " (" . $strPaymentMemo . ")";
                }

                $this->create([
                    "row_uuid" => Util::uuid(),
                    "user_id" => $objUser->user_id,
                    "user_uuid" => $objUser->user_uuid,
                    "project_id" => $objProject->project_id,
                    "project_uuid" => $objProject->project_uuid,
                    "platform_id" => $objPlatform->platform_id,
                    "platform_uuid" => $objPlatform->platform_uuid,
                    "user_balance" => $floatUserBalance,
                    "payment_amount" => $floatAmount,
                    "payment_memo" => "Revenue Earned from {$strMemo} for Project: {$objProject->project_title}",
                    "date_starts" => $dateStarts,
                    "date_ends" => $dateEnds,
                ]);
                $this->updateUsersTable($objUser->user_uuid, $floatUserBalance);
            }
        }

        return (true);
    }

    /**
     * @param array $arrWithdrawalArray
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    public function createWithdrawal(array $arrWithdrawalArray){
        $objUserCurrentBalance = $this->create([
            "row_uuid" => Util::uuid(),
            "user_id" => $arrWithdrawalArray["user_id"],
            "user_uuid" => $arrWithdrawalArray["user_uuid"],
            "user_balance" => $arrWithdrawalArray["user_balance"],
            "payment_amount" => $arrWithdrawalArray["payment_amount"],
            "payment_memo" => $arrWithdrawalArray["payment_memo"],
            "withdrawal_method" => $arrWithdrawalArray["withdrawal_method"],
            "withdrawal_method_data" => $arrWithdrawalArray["withdrawal_method_data"],
            "withdrawal_status" => $arrWithdrawalArray["withdrawal_status"],
            "withdrawal_method_id" => $arrWithdrawalArray["withdrawal_method_id"],
            "withdrawal_method_uuid" => $arrWithdrawalArray["withdrawal_method_uuid"],
        ]);
        $this->updateUsersTable($arrWithdrawalArray["user_uuid"], $arrWithdrawalArray["user_balance"]);

        return ($objUserCurrentBalance);
    }

    public function createFromCancelledWithdrawal(array $arrWithdrawalArray){
        $objUserCurrentBalance = $this->create([
            "row_uuid" => Util::uuid(),
            "user_id" => $arrWithdrawalArray["user_id"],
            "user_uuid" => $arrWithdrawalArray["user_uuid"],
            "user_balance" => $arrWithdrawalArray["user_balance"],
            "payment_amount" => $arrWithdrawalArray["payment_amount"],
            "payment_memo" => $arrWithdrawalArray["payment_memo"],
        ]);
        $this->updateUsersTable($arrWithdrawalArray["user_uuid"], $arrWithdrawalArray["user_balance"]);

        return ($objUserCurrentBalance);
    }

    /**
     * @param string|null $strStatus
     * @param int $perPage
     * @return mixed
     */
    public function getWithdrawalForOffice(?string $strStatus, int $perPage = 10){
        $query = $this->model->whereNotNull("withdrawal_status");

        if (!is_null($strStatus)) {
            $query = $query->where("withdrawal_status", $strStatus);
        }

        return ($query->orderBy("stamp_created_at", "desc")->paginate($perPage));
    }

    private function updateUsersTable(string $userUuid, float $userBalance){
        $objUser = User::where("user_uuid", $userUuid)->first();
        $objUser->update(["user_balance" => $userBalance]);
    }
}
