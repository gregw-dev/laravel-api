<?php

namespace App\Http\Controllers\Core;

use App\Http\{Controllers\Controller,
    Requests\Core\CreateCorrespondence,
    Requests\Core\IngestCorrespondence,
    Requests\Core\getCorrespondence,
};
use App\Services\Core\Correspondence as CorrespondenceService;
use Illuminate\Http\Response;
use App\Http\Resources\Core\CorrespondenceMessage as CorrespondenceMessageResource;


/**
 * @group Core
 *
 */
class Correspondence extends Controller
{
    /**
     * @var CorrespondenceService
     */
    private CorrespondenceService $correspondenceService;

    /**
     * Correspondence constructor.
     * @param CorrespondenceService $correspondenceService
     */
    public function __construct(CorrespondenceService $correspondenceService){
        $this->correspondenceService    = $correspondenceService;
    }

    /**
     * @group Core
     *
     * @param CreateCorrespondence $objRequest
     * @return mixed
     * @throws \Exception
     */
    public function createCorrespondence(CreateCorrespondence $objRequest){
        $attachments = $this->correspondenceService->combineAttachments($objRequest->file());
        $clientIp    = $objRequest->ip();
        $clientHost  = $objRequest->getHttpHost();
        $clientAgent  = $objRequest->server("HTTP_USER_AGENT");

//       $bnHasDuplicate = $this->correspondenceService->checkDuplicate($objRequest->input("email"),
//            $objRequest->input("subject"), $objRequest->input("json"),"json");
//        if ($bnHasDuplicate) {
//            return ($this->apiReject(null, "Duplicate Record.", 400));
//        }

        $objCorrespondence = $this->correspondenceService->create($objRequest->post(), $clientIp, $clientHost,$clientAgent, $attachments);

        if (is_null($objCorrespondence)) {
            return ($this->apiReject(null, "Correspondence hasn't created.", 400));
        }

        return ($this->apiReply($objCorrespondence, "Correspondence created successfully.", 200));
    }

    public function ingestCorrespondence(IngestCorrespondence $objRequest){
        if(!$this->correspondenceService->verifyWebhookSignature($objRequest->input("token"),$objRequest->input("timestamp"),$objRequest->input("signature"))){
             return $this->apiReject(null,"Correspondence From Mailgun not verified Successfully",406);
        }
        $strClientHost  = $objRequest->getHttpHost();
        $strClientIp  = $objRequest->ip();
        $strClientAgent  = $objRequest->header("User-Agent");
        $arrAttachments = $this->correspondenceService->combineAttachments($objRequest->file());
        $objCorrespondence = $this->correspondenceService->ingest($objRequest->post(),$strClientHost,$strClientIp,$strClientAgent,$arrAttachments);
        $strEnv = env("APP_ENV");
        
        if($objCorrespondence){
            return $this->apiReply(null,"Correspondence Logged Successfully on {$strEnv} Env");
        }else{
            return $this->apiReject(null,"Correspondence Discarded on {$strEnv} Env",Response::HTTP_NOT_ACCEPTABLE);
        }
    }

    public function getCorrespondence(getCorrespondence $objRequest){
        $objCorrespondence = $this->correspondenceService->getCorrespondence($objRequest->uuid, $objRequest->email);
        $arrResponse = $objCorrespondence->toArray();
        $arrResponse["messages"] = CorrespondenceMessageResource::collection($objCorrespondence->messages);

        return ($this->apiReply($arrResponse, "", Response::HTTP_OK));
    }


}
