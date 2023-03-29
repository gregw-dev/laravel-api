<?php

namespace App\Http\Controllers\Office;

use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Office\Bootloader as BootloaderService;

/**
 * @group Office Soundblock
 *
 */
class Bootloader extends Controller
{
    /** @var BootloaderService */
    private BootloaderService $bootloaderService;

    /**
     * Bootloader constructor.
     * @param BootloaderService $bootloaderService
     */
    public function __construct(BootloaderService $bootloaderService){
        $this->bootloaderService = $bootloaderService;
    }

    public function index(){
        $objUser = Auth::user();
        $objUser->update(["last_login" => now()]);
        $arrData = $this->bootloaderService->prepareDataForBootloader($objUser);

        return ($this->apiReply($arrData, "", Response::HTTP_OK));
    }

    /**
     * @return mixed
     */
    public function getAuthUserGroup(){
        $objUser = Auth::user();

        return ($this->apiReply($objUser->groups, "User groups", 200));
    }
}
