<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'INVOICEX') }} | Sign in</title>

    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172033;
            background: #f3f5f9;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 32px;
            background:
                radial-gradient(ellipse at 12% 8%, rgba(101, 119, 255, .08), transparent 34%),
                #f3f5f9;
        }

        button,
        input {
            font: inherit;
        }

        .login-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            width: min(100%, 1080px);
            min-height: 650px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e8ebf2;
            border-radius: 24px;
            box-shadow: 0 24px 70px rgba(28, 39, 67, .12);
        }

        .brand-panel {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: 48px;
            color: #fff;
            background:
                radial-gradient(ellipse at 10% 100%, rgba(107, 124, 255, .35), transparent 42%),
                linear-gradient(145deg, #121a31 0%, #202c50 100%);
        }

        .brand-panel::before,
        .brand-panel::after {
            position: absolute;
            z-index: 0;
            content: "";
            border: 1px solid rgba(255, 255, 255, .09);
            border-radius: 50%;
            pointer-events: none;
        }

        .brand-panel::before {
            top: 24%;
            right: -210px;
            width: 430px;
            height: 430px;
        }

        .brand-panel::after {
            top: 31%;
            right: -150px;
            width: 310px;
            height: 310px;
        }

        .brand,
        .brand-message,
        .feature-list,
        .panel-footer {
            position: relative;
            z-index: 1;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: #fff;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: .11em;
            text-decoration: none;
        }

        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            color: #fff;
            background: linear-gradient(145deg, #8794ff, #5f70e8);
            border-radius: 13px;
            box-shadow: 0 8px 20px rgba(74, 91, 214, .3);
        }

        .brand-mark svg {
            width: 23px;
            height: 23px;
        }

        .brand-message {
            max-width: 405px;
            margin: 64px 0 48px;
        }

        .eyebrow {
            margin: 0 0 17px;
            color: #9eabff;
            font-size: 11px;
            font-weight: 750;
            letter-spacing: .17em;
            text-transform: uppercase;
        }

        .brand-message h1 {
            max-width: 390px;
            margin: 0;
            font-size: clamp(36px, 4vw, 48px);
            font-weight: 700;
            letter-spacing: -.045em;
            line-height: 1.12;
        }

        .brand-message > p:last-child {
            max-width: 360px;
            margin: 20px 0 0;
            color: #c1c9df;
            font-size: 15px;
            line-height: 1.75;
        }

        .feature-list {
            display: grid;
            gap: 17px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #e3e7f2;
            font-size: 13px;
        }

        .feature-check {
            display: grid;
            width: 22px;
            height: 22px;
            flex: 0 0 22px;
            place-items: center;
            color: #c5cbff;
            background: rgba(144, 157, 255, .16);
            border: 1px solid rgba(184, 192, 255, .2);
            border-radius: 50%;
        }

        .feature-check svg {
            width: 12px;
            height: 12px;
        }

        .panel-footer {
            margin-top: 40px;
            color: #98a3bf;
            font-size: 11px;
        }

        .login-panel {
            display: grid;
            place-items: center;
            padding: 64px 56px;
        }

        .login-content {
            width: min(100%, 370px);
        }

        .mobile-brand {
            display: none;
        }

        .login-content .eyebrow {
            margin-bottom: 12px;
            color: #6879e8;
        }

        .login-content h2 {
            margin: 0;
            color: #182136;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -.04em;
        }

        .login-intro {
            margin: 10px 0 30px;
            color: #7a8497;
            font-size: 14px;
            line-height: 1.6;
        }

        .status-message {
            margin-bottom: 22px;
            padding: 12px 14px;
            color: #176444;
            background: #ecfdf5;
            border: 1px solid #bcebd5;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.5;
        }

        .form-field {
            margin-bottom: 20px;
        }

        .form-field label {
            display: block;
            margin-bottom: 8px;
            color: #374258;
            font-size: 13px;
            font-weight: 650;
        }

        .form-field input {
            display: block;
            width: 100%;
            height: 50px;
            padding: 0 14px;
            color: #172033;
            background: #fff;
            border: 1px solid #dce1ea;
            border-radius: 10px;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .form-field input::placeholder {
            color: #a4acbb;
        }

        .form-field input:focus {
            border-color: #6979ed;
            box-shadow: 0 0 0 4px rgba(105, 121, 237, .13);
        }

        .form-field input[aria-invalid="true"] {
            border-color: #d14343;
        }

        .field-error {
            margin: 7px 0 0;
            color: #b42318;
            font-size: 12px;
            line-height: 1.45;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 2px 0 25px;
        }

        .remember-option {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #657087;
            cursor: pointer;
            font-size: 12px;
        }

        .remember-option input {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: #6475e8;
        }

        .forgot-link {
            color: #5e6fdf;
            font-size: 12px;
            font-weight: 650;
            text-decoration: none;
        }

        .forgot-link:hover {
            color: #3e50c8;
            text-decoration: underline;
        }

        .submit-button {
            display: flex;
            width: 100%;
            min-height: 50px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 18px;
            color: #fff;
            background: linear-gradient(110deg, #6577e8, #5264d6);
            border: 0;
            border-radius: 10px;
            box-shadow: 0 8px 18px rgba(82, 100, 214, .2);
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .submit-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 11px 22px rgba(82, 100, 214, .28);
        }

        .submit-button:focus-visible,
        .forgot-link:focus-visible,
        .remember-option input:focus-visible {
            outline: 3px solid rgba(82, 100, 214, .35);
            outline-offset: 3px;
        }

        .submit-button svg {
            width: 17px;
            height: 17px;
        }

        @media (max-width: 760px) {
            body {
                padding: 0;
                background: #fff;
            }

            .login-shell {
                display: block;
                width: 100%;
                min-height: 100vh;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .brand-panel {
                display: none;
            }

            .login-panel {
                min-height: 100vh;
                padding: 40px 25px;
            }

            .mobile-brand {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 52px;
                color: #1b2540;
                font-size: 16px;
                font-weight: 800;
                letter-spacing: .1em;
                text-decoration: none;
            }

            .mobile-brand .brand-mark {
                width: 38px;
                height: 38px;
            }
        }

        @media (max-width: 380px) {
            .login-panel {
                padding-right: 20px;
                padding-left: 20px;
            }

            .form-options {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <main class="login-shell">
        <section class="brand-panel" aria-label="About INVOICEX">
            <a class="brand" href="{{ url('/') }}" aria-label="INVOICEX home">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M7 3.75h7l4 4V20a.75.75 0 0 1-.75.75h-10.5A.75.75 0 0 1 6 20V4.5a.75.75 0 0 1 .75-.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="M14 4v4h4M9 12h6M9 15.5h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span>INVOICEX</span>
            </a>

            <div class="brand-message">
                <p class="eyebrow">Invoice management, made simple</p>
                <h1>Keep your business moving.</h1>
                <p>One clear workspace for the invoices, customers, and payments that keep your business running.</p>
            </div>

            <ul class="feature-list">
                <li>
                    <span class="feature-check" aria-hidden="true">
                        <svg viewBox="0 0 16 16" fill="none"><path d="m3.5 8.2 2.8 2.7 6.2-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    Manage invoices and payments in one place
                </li>
                <li>
                    <span class="feature-check" aria-hidden="true">
                        <svg viewBox="0 0 16 16" fill="none"><path d="m3.5 8.2 2.8 2.7 6.2-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    Keep customer records close at hand
                </li>
            </ul>

            <div class="panel-footer">&copy; {{ date('Y') }} INVOICEX. All rights reserved.</div>
        </section>

        <section class="login-panel" aria-labelledby="login-title">
            <div class="login-content">
                <a class="mobile-brand" href="{{ url('/') }}" aria-label="INVOICEX home">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M7 3.75h7l4 4V20a.75.75 0 0 1-.75.75h-10.5A.75.75 0 0 1 6 20V4.5a.75.75 0 0 1 .75-.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="M14 4v4h4M9 12h6M9 15.5h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>INVOICEX</span>
                </a>

                <p class="eyebrow">Secure workspace access</p>
                <h2 id="login-title">{{ __('Welcome back') }}</h2>
                <p class="login-intro">{{ __('Sign in to manage your invoices and business.') }}</p>

                @if (session('status'))
                    <div class="status-message" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="form-field">
                        <label for="email">{{ __('Email address') }}</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="you@company.com"
                            required
                            autofocus
                            autocomplete="username"
                            @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif
                        >
                        @if ($errors->has('email'))
                            <p class="field-error" id="email-error">{{ $errors->first('email') }}</p>
                        @endif
                    </div>

                    <div class="form-field">
                        <label for="password">{{ __('Password') }}</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                            @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif
                        >
                        @if ($errors->has('password'))
                            <p class="field-error" id="password-error">{{ $errors->first('password') }}</p>
                        @endif
                    </div>

                    <div class="form-options">
                        <label class="remember-option" for="remember_me">
                            <input id="remember_me" type="checkbox" name="remember" value="1" @checked(old('remember'))>
                            <span>{{ __('Remember me') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="forgot-link" href="{{ route('password.request') }}">
                                {{ __('Forgot password?') }}
                            </a>
                        @endif
                    </div>

                    <button class="submit-button" type="submit">
                        <span>{{ __('Sign in') }}</span>
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
