<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasskeysController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        logger('passkeys', ['user' => $request->user()->id, 'request' => $request->all()]);

        $request->validate([
            'passkey' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->forceFill([
            'passkey' => Hash::make($request->passkey),
        ])->save();

        return redirect()->route('password.request')
            ->with('status', 'Your passkey has been set.');
    }
}
