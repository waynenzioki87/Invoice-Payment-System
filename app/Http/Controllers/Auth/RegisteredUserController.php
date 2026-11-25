<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CustomerWelcomeMail;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(
        Request $request,
        CustomerAccountService $customerAccounts
    ): RedirectResponse {

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:'.User::class,
            ],
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
        ]);

        $user = DB::transaction(function () use ($request, $customerAccounts): User {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'customer',
            ]);

            $customerAccounts->forUser($user);

            return $user;
        });

        // Trigger Laravel's email verification notification
        event(new Registered($user));

        // Log the newly registered user in
        Auth::login($user);

        // Send the welcome email
        Mail::to($user->email)->queue(new CustomerWelcomeMail($user));

        // Redirect to email verification instead of the dashboard
        return redirect()->route('verification.notice');
    }
}
