<?php
namespace App\Repositories\Soundblock\Payments;

use Util;
use Carbon\Carbon;
use App\Helpers\Math;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use App\Repositories\Soundblock\Platform as PlatformRepository;
use App\Models\Soundblock\Payments\MusicAccountPayment as MusicAccountPaymentModel;

class MusicAccountPayment extends BaseRepository
{
    /** @var PlatformRepository */
    private PlatformRepository $platformRepo;

    /**
     * MusicAccountPayment constructor.
     * @param MusicAccountPaymentModel $objMusicAccountPaymentModel
     * @param PlatformRepository $platformRepo
     */
    public function __construct(MusicAccountPaymentModel $objMusicAccountPaymentModel, PlatformRepository $platformRepo)
    {
        $this->model = $objMusicAccountPaymentModel;
        $this->platformRepo = $platformRepo;
    }

    public function storeOrUpdate(array $arrMusicUserPayments)
    {
        $objAppleMusicPlatform = $this->platformRepo->findByName("Apple Music");

        foreach ($arrMusicUserPayments as $arrPayment) {
            $objModel = $this->model->where("date_starts", $arrPayment["date_starts"])
                ->where("date_ends", $arrPayment["date_ends"])
                ->where("platform_id", $arrPayment["platform_id"])
                ->where("account_id", $arrPayment["account_id"])
                ->first();

            if (is_null($objModel)) {
                $this->model->create([
                    "payment_uuid"          => Util::uuid(),
                    "platform_id"       => $arrPayment["platform_id"],
                    "platform_uuid"     => $arrPayment["platform_uuid"],
                    "account_id"        => $arrPayment["account_id"],
                    "account_uuid"      => $arrPayment["account_uuid"],
                    "date_starts"       => $arrPayment["date_starts"],
                    "date_ends"         => $arrPayment["date_ends"],
                    "payment_amount"    => $arrPayment["payment_amount"],
                    "payment_memo"      => $arrPayment["payment_memo"],
                ]);
            } elseif(number_format($objModel->payment_amount, 10) != number_format($arrPayment["payment_amount"], 10)) {
                if ($objAppleMusicPlatform->platform_id == $objModel->platform_id) {
                    $objModel->update([
                        "payment_amount"    => $arrPayment["payment_amount"],
                        "payment_memo"      => $arrPayment["payment_memo"],
                    ]);
                } else {
                    $objModel->update([
                        "payment_amount"    => $arrPayment["payment_amount"],
                        "payment_memo"      => "Adjustment from ({$objModel->platform->name})",
                    ]);
                }
            }
        }
    }

    public function getBetweenDates($strAccountUuid, $dateStarts = null, $dateEnds = null, $strPlatformUuid = null):?Collection
    {
        $query = $this->model->where("account_uuid", $strAccountUuid);

        if (is_string($strPlatformUuid)) {
            $query = $query->where("platform_uuid", $strPlatformUuid);
        }

        if (is_string($dateStarts) && is_string($dateEnds)) {
            $strDateStarts = Carbon::createFromFormat("Y-m", $dateStarts)->startOfMonth()->format("Y-m-d");
            $strDateEnds = Carbon::createFromFormat("Y-m", $dateEnds)->endOfMonth()->format("Y-m-d");
            $query = $query->where("date_ends", ">=", $strDateStarts)->where("date_ends", "<=", $strDateEnds);
        }

        return ($query->get());
    }
}
