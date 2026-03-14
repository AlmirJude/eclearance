<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
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
                background: rgba(25, 5, 5, 0.70);
            }

            .auth-wrapper {
                position: relative;
                z-index: 1;
                width: 100%;
                max-width: 420px;
                padding: 1rem;
            }

            .auth-card {
                background: rgba(255, 255, 255, 0.06);
                backdrop-filter: blur(18px);
                -webkit-backdrop-filter: blur(18px);
                border: 1px solid rgba(255, 255, 255, 0.12);
                border-radius: 1.25rem;
                padding: 2.5rem 2.25rem;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(253,0,1,0.08);
                color: #ffffff;
            }

            .auth-card *:not(input):not(button) {
                color: #ffffff;
            }

            .auth-logo-wrap {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-bottom: 1.5rem;
                text-decoration: none;
            }

            .auth-logo {
                width: 90px;
                height: 90px;
                object-fit: contain;
                border-radius: 50%;
                margin-bottom: 0.9rem;
                filter: drop-shadow(0 4px 18px rgba(253, 0, 1, 0.40));
            }

            .auth-logo-wrap h1 {
                font-size: 1.2rem;
                font-weight: 700;
                color: #ffffff;
                letter-spacing: -0.01em;
                text-align: center;
                line-height: 1.3;
            }

            .auth-logo-wrap h1 span {
                color: #fd0001;
            }

            .auth-logo-wrap p {
                font-size: 0.82rem;
                color: rgba(255, 210, 210, 0.65);
                margin-top: 0.25rem;
                text-align: center;
            }

            /* --- Override Flux / Tailwind component styles inside auth card --- */
            .auth-card [data-flux-heading],
            .auth-card flux\\:heading,
            .auth-card h2 {
                color: #ffffff !important;
            }

            .auth-card label,
            .auth-card [class*="flux"] label {
                color: rgba(255, 220, 220, 0.85) !important;
                font-size: 0.875rem;
            }

            .auth-card input[type="text"],
            .auth-card input[type="email"],
            .auth-card input[type="password"] {
                background: rgba(255, 255, 255, 0.08) !important;
                border: 1px solid rgba(255, 255, 255, 0.18) !important;
                color: #ffffff !important;
                border-radius: 0.5rem !important;
                padding: 0.6rem 0.85rem !important;
                width: 100%;
                font-size: 0.95rem;
                font-family: inherit;
                outline: none;
                transition: border-color 0.2s, box-shadow 0.2s;
            }

            .auth-card input[type="text"]:focus,
            .auth-card input[type="email"]:focus,
            .auth-card input[type="password"]:focus {
                border-color: rgba(253, 0, 1, 0.60) !important;
                box-shadow: 0 0 0 3px rgba(253, 0, 1, 0.15) !important;
            }

            .auth-card input::placeholder {
                color: rgba(255, 200, 200, 0.35) !important;
            }

            /* Primary button (Log in) */
            .auth-card button[type="submit"],
            .auth-card [data-flux-button][data-variant="primary"] {
                background: #fd0001 !important;
                color: #ffffff !important;
                border: none !important;
                border-radius: 0.5rem !important;
                font-weight: 600 !important;
                font-size: 0.95rem !important;
                padding: 0.7rem 1.5rem !important;
                width: 100%;
                cursor: pointer;
                box-shadow: 0 4px 20px rgba(253, 0, 1, 0.45) !important;
                transition: background 0.2s, transform 0.15s, box-shadow 0.2s !important;
            }

            .auth-card button[type="submit"]:hover {
                background: #c70001 !important;
                transform: translateY(-1px);
                box-shadow: 0 6px 24px rgba(253, 0, 1, 0.55) !important;
            }

            .auth-card button[type="submit"]:active {
                transform: translateY(0);
            }

            /* Forgot password & links */
            .auth-card a {
                color: #ff7070 !important;
                text-decoration: none;
                transition: color 0.15s;
            }

            .auth-card a:hover {
                color: #fd0001 !important;
            }

            /* Checkbox */
            .auth-card input[type="checkbox"] {
                accent-color: #fd0001;
                width: 15px;
                height: 15px;
            }

            /* Subheading / description text */
            .auth-card [data-flux-subheading],
            .auth-card p {
                color: rgba(255, 210, 210, 0.65) !important;
                font-size: 0.85rem;
            }

            /* Session status */
            .auth-card .text-center {
                color: #ffb3b3;
            }

            footer.auth-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 1;
                color: rgba(255, 200, 200, 0.35);
                font-size: 0.74rem;
                padding: 0.9rem;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="auth-wrapper">
            <div class="auth-card">
                <a href="{{ route('home') }}" class="auth-logo-wrap" wire:navigate>
                    <img src="/SHC-2021.webp" alt="School Logo" class="auth-logo">
                    <h1>Student <span>eClearance</span> System</h1>
                    <p>Official School Portal</p>
                </a>

                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        <footer class="auth-footer">&copy; {{ date('Y') }} eClearance. All rights reserved.</footer>

        @fluxScripts
    </body>
</html>
