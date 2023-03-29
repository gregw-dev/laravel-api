<?php

namespace App\Jobs\Soundblock\Reports;

use Mail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Soundblock\Platform as PlatformModel;
use App\Models\Soundblock\Payments\MusicUserBalancePayment;
use App\Mail\Soundblock\Reports\ProcessedReportsMailing;
use App\Models\Users\User as UserModel;

class UsersMailing implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $arrData = [];
        $objCarbonNow = Carbon::now();
        $objRecords = MusicUserBalancePayment::whereNull("withdrawal_method")->where("platform_id", "!=", 0)->where("stamp_created_at", ">", $objCarbonNow->subMinutes(20)->format("Y-m-d h:i:s"))->get();
        $objAppleMusicPlatform = PlatformModel::where("name", "Apple Music")->first();
        foreach ($objRecords->groupBy("user_id") as $intUserId => $objUserRecords) {
            $arrPlatforms = [];
            foreach ($objUserRecords->groupBy("platform_id") as $ibtPlatformId => $objPlatformRecords) {
                foreach ($objPlatformRecords as $objRecord) {
                    $arrInsertData = [
                        "platform_id" => $ibtPlatformId,
                        "date_starts" => Carbon::parse($objRecord->date_starts)->format("m/d/Y"),
                        "date_ends" => Carbon::parse($objRecord->date_ends)->format("m/d/Y"),
                    ];

                    if ($objAppleMusicPlatform->platform_id == $ibtPlatformId) {
                        preg_match("/\((.*)\)/", $objRecord->payment_memo, $matches);
                        $arrInsertData["memo"] = $matches[1];
                    }

                    $arrPlatforms[] = $arrInsertData;
                }
            }

            $arrGroupedByPlatformTemp = [];
            foreach ($arrPlatforms as $arrPlatformData) {
                $arrGroupedByPlatformTemp[$arrPlatformData["platform_id"]][] = $arrPlatformData;
            }

            if (array_key_exists($objAppleMusicPlatform->platform_id, $arrGroupedByPlatformTemp)) {
                $arrTemp = [];
                foreach ($arrGroupedByPlatformTemp[$objAppleMusicPlatform->platform_id] as $arrPlatformData) {
                    $arrTemp[$arrPlatformData["date_starts"]][] = $arrPlatformData;
                }

                foreach ($arrTemp as $strDateStart => $arrTempPlatformData) {
                    $strPlatformTypes = implode(", ", array_unique(array_column($arrTempPlatformData, "memo")));

                    foreach ($arrPlatforms as $index => $arrPlatform) {
                        if ($arrPlatform["platform_id"] == $objAppleMusicPlatform->platform_id && $arrPlatform["date_starts"] == $strDateStart) {
                            $arrPlatforms[$index]["memo"] = $strPlatformTypes;
                        }
                    }
                }
            }

            $arrData[$intUserId] = array_map("unserialize", array_unique(array_map("serialize", $arrPlatforms)));
        }

        foreach ($arrData as $intUserId => $arrUserData) {
            $objUser = UserModel::find($intUserId);
            if ($objUser) {
                Mail::to($objUser->primary_email->user_auth_email)->send(new ProcessedReportsMailing($arrUserData, $objUser));
            }
        }
    }
}
