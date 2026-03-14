<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>eClearance &mdash; Student Clearance System</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            html, body { height: 100%; }
            body {
                font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                min-height: 100vh;
                display: grid;
                place-items: center;
                background-image: url('/bg.jpg');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                position: relative;
            }
            body::before {
                content: '';
                position: absolute;
                inset: 0;
                background: rgba(25, 5, 5, 0.68);
            }
            .page-wrapper {
                position: relative;
                z-index: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 2.5rem 2rem;
                text-align: center;
                width: 100%;
                max-width: 660px;
            }
            .logo {
                width: 110px;
                height: 110px;
                object-fit: contain;
                border-radius: 50%;
                margin-bottom: 1.25rem;
                filter: drop-shadow(0 4px 20px rgba(253, 0, 1, 0.35));
            }
            .badge {
                display: inline-block;
                background: rgba(253, 0, 1, 0.18);
                border: 1px solid rgba(253, 0, 1, 0.45);
                color: #ffb3b3;
                font-size: 0.72rem;
                font-weight: 600;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                padding: 0.35rem 1.1rem;
                border-radius: 999px;
                margin-bottom: 1.4rem;
                backdrop-filter: blur(4px);
            }
            h1 {
                font-size: clamp(2rem, 5vw, 3.4rem);
                font-weight: 700;
                color: #ffffff;
                line-height: 1.15;
                margin-bottom: 1.1rem;
                letter-spacing: -0.02em;
            }
            h1 span {
                color: #fd0001;
            }
            .description {
                font-size: 1rem;
                color: rgba(255, 220, 220, 0.80);
                line-height: 1.75;
                max-width: 500px;
                margin: 0 auto 2rem;
            }
            .features {
                display: flex;
                flex-wrap: wrap;
                gap: 0.65rem;
                justify-content: center;
                margin-bottom: 2.25rem;
            }
            .feature-chip {
                display: flex;
                align-items: center;
                gap: 0.45rem;
                background: rgba(253, 0, 1, 0.12);
                border: 1px solid rgba(253, 0, 1, 0.30);
                color: #ffd5d5;
                font-size: 0.8rem;
                font-weight: 500;
                padding: 0.38rem 0.9rem;
                border-radius: 999px;
                backdrop-filter: blur(4px);
            }
            .feature-chip svg {
                flex-shrink: 0;
                color: #fd0001;
                opacity: 0.9;
            }
            .btn-login {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                background: #fd0001;
                color: #ffffff;
                font-family: inherit;
                font-size: 1rem;
                font-weight: 600;
                padding: 0.8rem 2.25rem;
                border-radius: 0.5rem;
                border: none;
                cursor: pointer;
                text-decoration: none;
                transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
                box-shadow: 0 4px 22px rgba(253, 0, 1, 0.50);
            }
            .btn-login:hover {
                background: #c70001;
                transform: translateY(-2px);
                box-shadow: 0 8px 28px rgba(253, 0, 1, 0.55);
            }
            .btn-login:active {
                transform: translateY(0);
                box-shadow: 0 2px 10px rgba(253, 0, 1, 0.40);
            }
            footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 1;
                color: rgba(255, 200, 200, 0.40);
                font-size: 0.76rem;
                padding: 1rem;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="page-wrapper">
            <img src="/SHC-2021.webp" alt="School Logo" class="logo">
            <span class="badge">School Clearance</span>

            <h1>Student <span>eClearance</span><br>System</h1>

            <p class="description">
                A digital platform that streamlines the HED student clearance process &mdash;
                allowing students to submit requirements, track approvals, and get cleared
                by all departments, offices, and organizations in one place.
            </p>


            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn-login">
                        Go to Dashboard
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-login">
                        Log In to Your Account
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                @endauth
            @endif
        </div>

        <footer>&copy; {{ date('Y') }} eClearance. All rights reserved.</footer>
    </body>
</html>
