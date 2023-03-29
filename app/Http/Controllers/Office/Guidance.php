<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Http\Requests\Office\Guidance\CreateGuidance;
use App\Http\Requests\Office\Guidance\EditGuidance;
use App\Http\Requests\Office\Guidance\ListAllGuidance;
use App\Http\Requests\Office\Guidance\ListGuideRefs;
use App\Http\Requests\Office\Guidance\replyFeedback;
use App\Services\Soundblock\Guidance as GuidanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\Soundblock\Guidance as GuidanceResource;

class Guidance extends Controller
{

    const PERMISSION_NAME = "Arena.Support.Soundblock";
    const PERMISSION_GROUP = "Arena.Support";

    protected GuidanceService $guidanceService;
    /**
     * @param GuidanceService $guidanceService
     */
    public function __construct(GuidanceService $guidanceService)
    {
        $this->guidanceService = $guidanceService;
    }
    /**
     * @param Request $objReequest
     * @response Response
     */
    public function createGuidanceInstruction(CreateGuidance $objRequest)
    {
        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }

        $objGuidance = $this->guidanceService->create($objRequest->post());
        return $this->apiReply($objGuidance, "New Soundblock Guidance Instruction Created Successfully");
    }

    public function editGuidanceInstruction(EditGuidance $objRequest, $strGuideRef)
    {

        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }
        $objGuidance = $this->guidanceService->edit($strGuideRef, $objRequest->post());
        return $this->apiReply($objGuidance, "Soundblock Guidance Instruction Updated Successfully");
    }

    public function listAllGuidanceInstructions(ListAllGuidance $objRequest)
    {
        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }

        $objGuidance = $this->guidanceService->listAll($objRequest->all());

        return ($this->apiReply($objGuidance, "Soundblock guidance loaded successfully", Response::HTTP_OK));
    }

    public function getAGuidanceInstruction($strGuideRef)
    {

        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }
        $objGuidance = $this->guidanceService->fetchGuide($strGuideRef);
        return $this->apiReply(new GuidanceResource($objGuidance), "Soundblock Guidance Data fetched successfully");
    }

    public function deleteGuidanceInstruction($strGuideRef)
    {
        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }
        $this->guidanceService->deleteGuide($strGuideRef);
        return $this->apiReply(null, "Guide deleted successfully");
    }

    public function replyToFeedback(replyFeedback $objRequest)
    {
        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }
        $clientIp    = $objRequest->ip();
        $clientHost  = $objRequest->getHttpHost();
        $clientAgent  = $objRequest->server("HTTP_USER_AGENT");
        $objGuideFeedback = $this->guidanceService->replyToFeedback($objRequest->post(), $clientIp, $clientHost, $clientAgent);
        return $this->apiReply($objGuideFeedback, "Reply was added to Guide feedback successfully");
    }

    public function getGuideFeedbacks(String $strGuideRef)
    {
        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }
        $objGuide = $this->guidanceService->fetchGuide($strGuideRef);
        if (!$objGuide) {
            return $this->apiReject(null, "Guide not found");
        }
        return $this->apiReply($objGuide->feedbacks, "Guide feedbacks obtained successfully");
    }

    public function getFeedbackReplies(String $strFeedbackUuid)
    {
        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }

        $objFeedback = $this->guidanceService->fetchFeedback($strFeedbackUuid);
        if (!$objFeedback) {
            return $this->apiReject(null, "Feedback not found");
        }

        return $this->apiReply($objFeedback->replies, "Feedback replies retrieved successfully");
    }

    public function listGuideRefs(ListGuideRefs $objRequest)
    {
        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }
        $arrGuideRefs = $this->guidanceService->listGuideRefs($objRequest->filter);
        return $this->apiReply($arrGuideRefs,"Guide Refs Loaded Successfully");
    }

    public function listGuideTitles(ListGuideRefs $objRequest)
    {
        if (!is_authorized(auth()->user(), self::PERMISSION_GROUP, self::PERMISSION_NAME, "Office")) {
            return $this->apiReject("You do not possess required permission to perform this Operation");
        }
        $arrGuideTitles = $this->guidanceService->listGuideTitles($objRequest->filter);
        return $this->apiReply($arrGuideTitles,"Guide Titles Loaded Successfully");
    }
}
