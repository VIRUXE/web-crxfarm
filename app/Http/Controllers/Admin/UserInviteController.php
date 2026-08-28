<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserInvited;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class UserInviteController extends Controller
{
    public function create()
    {
        return view('admin.users.invite');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => true,
            'status' => User::STATUS_INVITED,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'onboarding.pin.create',
            now()->addHours(48),
            ['user' => $user->id],
        );

        // MAIL_MAILER is currently 'log' (no real transactional email set up
        // yet) — the message still gets built/sent as a real Mailable so it
        // works unmodified once real mail is configured, but for now the
        // inviting admin also gets the link directly so onboarding isn't
        // blocked on checking storage/logs/laravel.log.
        Mail::to($user->email)->send(new UserInvited($user, $signedUrl));

        return redirect()
            ->route('admin.users.invite.create')
            ->with('invite_link', $signedUrl)
            ->with('invited_email', $user->email);
    }
}
