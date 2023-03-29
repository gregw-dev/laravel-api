<?php

namespace App\Services\Soundblock;

use App\Helpers\Util;
use App\Models\Soundblock\GuidanceFeedback;
use App\Repositories\Soundblock\Guidance as GuidanceRepository;
use App\Repositories\Soundblock\GuidanceFeedback as GuidanceFeedbackRepository;
use App\Repositories\Soundblock\GuidanceRatings as GuidanceRatingsRepository;
use Exception;
use Illuminate\Support\Arr;

class Guidance
{

    protected GuidanceRepository $guidanceRepository;
    protected GuidanceRatingsRepository $guidanceRatingsRepository;
    protected GuidanceFeedbackRepository $guidanceFeedbackRepository;
    /**
     * @param GuidanceRepository $guidanceRepository
     * @param GuidanceRatingsRepository $guidanceRatingsRepository
     * @param GuidanceFeedbackRepository $guidanceFeedbackRepository
     */
    public function __construct(
        GuidanceRepository $guidanceRepository,
        GuidanceRatingsRepository $guidanceRatingsRepository,
        GuidanceFeedbackRepository $guidanceFeedbackRepository
    ) {
        $this->guidanceRepository = $guidanceRepository;
        $this->guidanceRatingsRepository = $guidanceRatingsRepository;
        $this->guidanceFeedbackRepository = $guidanceFeedbackRepository;
    }

    public function create($arrFormData)
    {
        $objGuidanceInstruction = $this->guidanceRepository->findByGuideRef($arrFormData["guide_ref"]);
        if($objGuidanceInstruction){
            throw new Exception("Error Creating Guide Instruction, an instruction already exists with the Same Guide Reference.");
        }
        return $this->guidanceRepository->create($arrFormData);
    }

    public function edit(String $strGuideRef, array $arrFormData)
    {
        $objGuidance = $this->guidanceRepository->findByGuideRef($strGuideRef);
        if (!$objGuidance) {
            throw new Exception("Guide not found");
        }

        if (isset($arrFormData["guide_ref"]) && $arrFormData["guide_ref"] != $strGuideRef) {
            $objGuideExists = $this->guidanceRepository->findByGuideRef($arrFormData["guide_ref"]);
            if ($objGuideExists) {
            throw new Exception("Error Updating Guide Instruction, an instruction already exists with the Same Guide Reference.");
            }
        }

        return $this->guidanceRepository->update($objGuidance, $arrFormData);
    }

    public function fetchGuide(String $strGuideRef)
    {
        $objGuidance = $this->guidanceRepository->findByGuideRef($strGuideRef);
        if (!$objGuidance) {
            throw new Exception("Guide not found");
        }
        return $objGuidance;
    }

    public function deleteGuide(String $strGuideRef)
    {
        $objGuidance = $this->fetchGuide(($strGuideRef));
        if ($objGuidance->flag_active == true) {
            throw new Exception("An active guide cannot be deleted");
        }
        $objGuidance->delete();
        return $objGuidance;
    }

    public function listAll(Array $arrParams)
    {
        $objGuidance = $this->guidanceRepository->listAll($arrParams);

        return ($objGuidance);
    }

    public function checkDuplicateFeedback($objUser, $objGuidance, $strUserFeedback)
    {
        return $this->guidanceFeedbackRepository->checkDuplicate($objUser, $objGuidance, $strUserFeedback);
    }

    public function storeGuidanceFeedback($arrFormData, $clientIp, $clientHost, $clientAgent)
    {
        $objUser = auth()->user();
        $objGuide = $this->fetchGuide($arrFormData["guide_ref"]);
        $blnIsDuplicate = $this->checkDuplicateFeedback($objUser, $objGuide, $arrFormData["user_feedback"]);
        if ($blnIsDuplicate) {
            throw new Exception("Duplicate feedback detected.");
        }
        $arrFormData["feedback_uuid"] = Util::uuid();
        $arrFormData["remote_addr"]  = $clientIp;
        $arrFormData["remote_host"] = $clientHost;
        $arrFormData["remote_agent"] = $clientAgent;
        $arrFormData["user_id"] = $objUser->user_id;
        $arrFormData["user_uuid"] = $objUser->user_uuid;
        $arrFormData["guide_id"] = $objGuide->guide_id;
        $arrFormData["guide_uuid"] = $objGuide->guide_uuid;
        unset($arrFormData["guide_ref"]);
        $objGuidanceFeedback = $this->guidanceFeedbackRepository->create($arrFormData);
        return $objGuidanceFeedback;
    }

    public function replyToFeedback($arrFormData, $clientIp, $clientHost, $clientAgent)
    {
        $objUser = auth()->user();
        $objGuideFeedback = $this->guidanceFeedbackRepository->find($arrFormData["feedback_uuid"]);
        $objGuide = $objGuideFeedback->guidance;
        $blnIsDuplicate = $this->checkDuplicateFeedback($objUser, $objGuide, $arrFormData["text"]);
        if ($blnIsDuplicate) {
            throw new Exception("Duplicate feedback detected.");
        }
        $arrFormData["feedback_uuid"] = Util::uuid();
        $arrFormData["remote_addr"]  = $clientIp;
        $arrFormData["remote_host"] = $clientHost;
        $arrFormData["remote_agent"] = $clientAgent;
        $arrFormData["user_id"] = $objUser->user_id;
        $arrFormData["user_uuid"] = $objUser->user_uuid;
        $arrFormData["guide_id"] = $objGuide->guide_id;
        $arrFormData["guide_uuid"] = $objGuide->guide_uuid;
        $arrFormData["parent_id"] = $objGuideFeedback->feedback_id;
        $arrFormData["parent_uuid"] = $objGuideFeedback->feedback_uuid;
        $arrFormData["user_feedback"] = $arrFormData["text"];
        unset($arrFormData["text"]);
        $this->guidanceFeedbackRepository->create($arrFormData);
        return $objGuideFeedback;
    }

    public function fetchFeedback(String $strFeedbackUuid): ?GuidanceFeedback
    {
        return $this->guidanceFeedbackRepository->find($strFeedbackUuid);
    }

    public function rateGuide(String $strGuideRef, float $userGuiderating)
    {
        $objGuide = $this->fetchGuide($strGuideRef);
        $objUser = auth()->user();
        $objUserGuideRating = $this->guidanceRatingsRepository->findByUser($objUser, $objGuide);
        if ($objUserGuideRating) {
            return $this->guidanceRatingsRepository->update($objUserGuideRating, ["user_rating" => $userGuiderating]);
        } else {
            return $this->guidanceRatingsRepository->create([
                "rating_uuid" => Util::uuid(),
                "guide_id" => $objGuide->guide_id,
                "guide_uuid" => $objGuide->guide_uuid,
                "user_id" => $objUser->user_id,
                "user_uuid" => $objUser->user_uuid,
                "user_rating" => $userGuiderating
            ]);
        }
    }

    public function listGuideRefs($strFilter)
    {
        return $this->guidanceRepository->listRefs($strFilter);
    }

    public function listGuideTitles($strFilter)
    {
        return $this->guidanceRepository->listTitles($strFilter);
    }
}
