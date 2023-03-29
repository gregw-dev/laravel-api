<?php

namespace App\Http\Controllers\Merch\Tourmask;

use Util;
use App\Services\Common\App as AppService;
use App\Mail\TourMask\HandleOrder;
use Illuminate\Support\Facades\Mail;
use App\Http\{Controllers\Controller, Requests\Tourmask\HandleOrder as HandleOrderRequest};

/**
 * @group Merch Tourmask
 *
 */
class Tourmask extends Controller
{
    /**
     * @group Merch
     * @bodyParam first_name string required
     * @bodyParam last_name string required
     * @bodyParam organization string required
     * @bodyParam email string required
     * @bodyParam message string required
     *
     * @param HandleOrderRequest $handleOrderRequest
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function handleOrder(HandleOrderRequest $handleOrderRequest) {
        try {
            $appService = resolve(AppService::class);
            $objApp = $appService->findOneByName("tourmask");
            $objUsers = Util::getUsersByPermissionAndGroup("Arena.Support", "Arena.Support.Merchandising");

            if ($objUsers->count() > 0) {
                foreach ($objUsers as $objUser) {
                    notify($objUser, $objApp, "Merch Notification", "Tourmask Order");
                    Mail::to($objUser->primary_email->user_auth_email)->send(new HandleOrder($handleOrderRequest->all()));
                }
            } else {
                Mail::to(env("MERCH_ORDER_EMAIL"))->send(new HandleOrder($handleOrderRequest->all()));
            }

            return response()->json("");
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}
