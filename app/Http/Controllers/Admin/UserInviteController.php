<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class UserInviteController extends Controller
{
    public function create()
    {
        return view('admin.users.invite');
    }

    /**
     * No email is ever sent — this app is PIN + passkey only, with no
     * delivery channel at all. The admin creating the account gets the
     * one-time signed link on screen and hands it to the new user however
     * they like (in person, text, whatever) — out of band, by design.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:255', 'unique:users,username'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'is_admin' => true,
            'status' => User::STATUS_INVITED,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'onboarding.pin.create',
            now()->addHours(48),
            ['user' => $user->id],
        );

        return redirect()
            ->route('admin.users.invite.create')
            ->with('invite_link', $signedUrl)
            ->with('invited_username', $user->username);
    }
}
