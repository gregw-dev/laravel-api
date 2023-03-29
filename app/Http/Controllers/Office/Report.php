<?php

namespace App\Http\Controllers\Office;

use Auth;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Office\Soundblock\Report\GetReportsMetadata;
use App\Http\Requests\Office\Soundblock\Report\UploadReports;
use App\Services\Office\Soundblock\Report as ReportService;

/**
 * @group Office Soundblock
 *
 */
class Report extends Controller{
    /** @var ReportService */
    private ReportService $reportService;

    /**
     * Report constructor.
     * @param ReportService $reports
     */
    public function __construct(ReportService $reports){
        $this->reportService = $reports;
    }

    public function getReports(GetReportsMetadata $objRequest){
        if (!is_authorized(Auth::user(), "Arena.Office", "Arena.Office.Access", "office")) {
            return $this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN);
        }

        $objRecords = $this->reportService->getReports(
            $objRequest->input("platform", null),
            $objRequest->input("date", null),
            $objRequest->input("status", null),
            $objRequest->input("per_page", 10)
        );

        return ($this->apiReply($objRecords, "", Response::HTTP_OK));
    }

    /**
     * @param UploadReports $objRequest
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|Response|object
     */
    public function storeReports(UploadReports $objRequest){
        $objUser = Auth::user();

        if (!is_authorized($objUser, "Arena.Office", "Arena.Office.Access", "office")) {
            return $this->apiReject("", "You don't have required permissions.", Response::HTTP_FORBIDDEN);
        }

        $this->reportService->processAppleReports($objUser, $objRequest->file("files"));
        $objRecords = $this->reportService->getReports();

        return ($this->apiReply($objRecords, "Reports is processing.", 200));
    }
}
