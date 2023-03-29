<?php
namespace App\Http\Controllers\Soundblock;

use App\Http\Controllers\Controller;
use App\Services\Soundblock\Guidance as GuidanceService;
use App\Http\Resources\Soundblock\Guidance as GuidanceResource;
use App\Http\Resources\Soundblock\GuidanceFeedback;
use App\Http\Requests\Soundblock\Guidance\CreateGuidanceFeedback;
use App\Http\Requests\Soundblock\Guidance\RateGuide;

class Guidance extends Controller {

    protected GuidanceService $guidanceService;

    /**
     * @param GuidanceService $guidanceService
     */
    public function __construct(GuidanceService $guidanceService){

        $this->guidanceService = $guidanceService;
    }

    public function getGuidance($strGuideRef) {

        $objGuidance = $this->guidanceService->fetchGuide($strGuideRef);
        return $this->apiReply(new GuidanceResource($objGuidance), "Soundblock Guidance Data fetched successfully");
    }

    public function storeGuidanceFeedback(CreateGuidanceFeedback $objRequest)
    {
        $clientIp    = $objRequest->ip();
        $clientHost  = $objRequest->getHttpHost();
        $clientAgent  = $objRequest->server("HTTP_USER_AGENT");
        $objGuidanceFeedback = $this->guidanceService->storeGuidanceFeedback($objRequest->post(), $clientIp, $clientHost, $clientAgent);
        return  $this->apiReply(new GuidanceFeedback($objGuidanceFeedback),"Feedback added to Guide successfully");
    }

    public function rateGuide(RateGuide $objRequest) {
    $objUserGuideRating = $this->guidanceService->rateGuide($objRequest->guide_ref,$objRequest->user_rating);
    return $this->apiReply($objUserGuideRating,"Guide has been rated successfully");
    }

}
