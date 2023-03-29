<?php

namespace App\Http\Controllers\Core;

use App\Helpers\ShellCommand;
use App\Jobs\Soundblock\Reports\Apple\ProcessAppleReport as ProcessAppleReportJob;
use Auth;
use Cache;
use Artisan;
use App\Models\Core\App;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use App\Console\Commands\DeleteProdLocalFiles;
use App\Console\Commands\Music\AllMusic\GetDataFromAllMusic;
use App\Console\Commands\Soundblock\UpdateReportsUnmatchedTable;
use App\Console\Commands\Soundblock\ProcessUnmatchedReports;

/**
 * @group Core
 *
 */
class Server extends Controller {

    public function ping() {
        return ($this->apiReply());
    }

    public function get() {
        return ($this->apiReply(App::all()));
    }

    public function version() {
        if (file_exists(base_path("version"))) {
            return ($this->apiReply(file_get_contents(base_path("version"))));
        }

        return ($this->apiReply("develop", "", 400));
    }

    public function flushCache(){
        if (!is_authorized(Auth::user(), "Arena.Developers", "Arena.Developers.Default")) {
            return $this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN);
        }

        Cache::flush();

        return ($this->apiReply(null, "Cache flushed successfully.", Response::HTTP_OK));
    }

    public function allMusicScript(){
        $objUser = Auth::user();

        if ($objUser->user_id == 3) {
            dispatch(new GetDataFromAllMusic());
        }

        return ($this->apiReply(null, "", Response::HTTP_OK));
    }

    public function runRevenueReports(){
        $objUser = Auth::user();

        if (!is_authorized($objUser, "Arena.Developers", "Arena.Developers.Default")) {
            return $this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN);
        }

        Artisan::call("process-reports");

        return ($this->apiReply(null, "", Response::HTTP_OK));
    }

    public function updateUpcRecords(){
        $objUser = Auth::user();

        if ($objUser->user_id == 3) {
            dispatch(new UpdateReportsUnmatchedTable());
        }

        return ($this->apiReply(null, "", Response::HTTP_OK));
    }

    public function processUnmatchedRecords(){
        $objUser = Auth::user();

        if ($objUser->user_id == 3) {
            dispatch(new ProcessUnmatchedReports());
        }

        return ($this->apiReply(null, "", Response::HTTP_OK));
    }

    public function deleteProdLocalFiles(){
        $objUser = Auth::user();

        if ($objUser->user_id == 3) {
            dispatch(new DeleteProdLocalFiles());
        }

        return ($this->apiReply(null, "", Response::HTTP_OK));
    }
}
