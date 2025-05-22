<!DOCTYPE html>
<html lang="en">

<head>
    <base href="../../../" />
    <title>Login - Metronic</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        body {
            background-image: url('assets/media/auth/bg-parachute.png');
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        [data-bs-theme="dark"] body {
            background-image: url('assets/media/auth/bg4-dark.jpg');
        }

        .auth-card {
            width: 100%;
            max-width: 500px;
            max-height: 500px;
        }

        .frosted-glass {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            box-shadow: 4px 8px 32px 0 rgba(31, 38, 135, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        [data-bs-theme="dark"] .frosted-glass {
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>

<body id="kt_body" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-column flex-column-fluid justify-content-center align-items-center min-vh-100 p-12">
            <div class="position-relative d-flex flex-column align-items-center w-100">
                <img src="{{ asset('assets/media/auth/logo-tniau.png') }}"
                    alt="Logo"
                    class="position-absolute translate-middle-y"
                    style="top: -40px; height: 100px;" />
                <div class="bg-body rounded-4 p-10 mt-10 frosted-glass auth-card">
                    <div class="d-flex flex-column align-items-center w-100 px-lg-10">
                        <form method="POST" action="{{ route('login') }}" class="form w-100" novalidate>
                            @csrf
                            <div class="text-center mb-11">
                                <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
                                <span>Silakan masuk untuk melanjutkan</span>
                            </div>
                            <div class="fv-row mb-8">
                                <input type="text" name="username" placeholder="Username" autocomplete="off"
                                    class="form-control bg-transparent @error('username') is-invalid @enderror"
                                    value="{{ old('username') }}" />
                                @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="fv-row mb-3">
                                <input type="password" name="password" placeholder="Password" autocomplete="off"
                                    class="form-control bg-transparent @error('password') is-invalid @enderror" />
                                @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-grid mt-15 mb-10">
                                <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                                    <span class="indicator-label fw-bolder">Sign In</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/custom/authentication/sign-in/general.js') }}"></script>
</body>

</html>