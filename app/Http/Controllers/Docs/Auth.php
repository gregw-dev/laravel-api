<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Core\Auth\AuthController;
use App\Http\Requests\Auth\Signin;
use App\Models\Users\User;

class Auth extends AuthController
{
    public function submit(Signin $objRequest)
    {
        $objResponse = $this->signIn($objRequest);
        if ($objResponse->isOk()) {
            /** @var User $objUser */
            $objUser = (new User)->findForPassport($objRequest->get('user'));
            \Auth::guard('web')->login($objUser);
            $objRequest->session()->regenerate();
            return redirect()->intended('/apidocsv1');
        }

        return back()->withErrors([
            'user' => "The provided credentials do not match our records.",
        ]);
    }
}
