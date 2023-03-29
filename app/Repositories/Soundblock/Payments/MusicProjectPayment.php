<?php
namespace App\Repositories\Soundblock\Payments;

use Util;
use Carbon\Carbon;
use App\Helpers\Math;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use App\Models\Soundblock\Payments\MusicProjectPayment as MusicProjectPaymentModel;

class MusicProjectPayment extends BaseRepository
{

    /**
     * MusicAccountPayment constructor.
     */
    public function __construct(MusicProjectPaymentModel $objMusicProjectPaymentModel )
    {
        $this->model = $objMusicProjectPaymentModel;
    }

    public function storeOrUpdate(array $arrMusicProjectPayments)
    {
        foreach ($arrMusicProjectPayments as $arrPayment) {
            $objModel = $this->model->where("date_starts", $arrPayment["date_starts"])
                ->where("date_ends", $arrPayment["date_ends"])
                ->where("platform_id", $arrPayment["platform_id"])
                ->where("project_id", $arrPayment["project_id"])
                ->first();

            if (is_null($objModel)) {
                $this->model->create([
                    "payment_uuid"          => Util::uuid(),
                    "platform_id"       => $arrPayment["platform_id"],
                    "platform_uuid"     => $arrPayment["platform_uuid"],
                    "project_id"        => $arrPayment["project_id"],
                    "project_uuid"      => $arrPayment["project_uuid"],
                    "date_starts"       => $arrPayment["date_starts"],
                    "date_ends"         => $arrPayment["date_ends"],
                    "payment_amount"    => $arrPayment["payment_amount"],
                    "payment_memo"      => $arrPayment["payment_memo"],
                ]);
            } elseif(number_format($objModel->payment_amount, 10) != number_format($arrPayment["payment_amount"], 10)) {
                $objModel->update([
                    "payment_amount"    => $arrPayment["payment_amount"],
                    "payment_memo"      => $arrPayment["payment_memo"]
                ]);
            }
        }
    }

    public function getBetweenDates($strProjectUuid, $dateStarts = null, $dateEnds = null, $strPlatformUuid = null):?Collection
    {
        $query = $this->model->where("project_uuid", $strProjectUuid);

        if (is_string($strPlatformUuid)) {
            $query = $query->where("platform_uuid", $strPlatformUuid);
        }

        if (is_string($dateStarts) && is_string($dateEnds)) {
            $strDateStarts = Carbon::createFromFormat("Y-m", $dateStarts)->startOfMonth()->format("Y-m-d");
            $strDateEnds = Carbon::createFromFormat("Y-m", $dateEnds)->endOfMonth()->format("Y-m-d");
            $query = $query->where("date_ends", ">=", $strDateStarts)->where("date_ends", "<=", $strDateEnds);
        }

        return ($query->orderBy("date_ends")->get());
    }

    public function getAvailableProjectPlatforms(string $strProjectUuid){
        return ($this->model->where("project_uuid", $strProjectUuid)->select("platform_uuid")->get());
    }
}
