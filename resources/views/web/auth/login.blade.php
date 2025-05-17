<!DOCTYPE html>
<html lang="en">

<head>
    <base href="../../../" />
    <title>Login - Metronic</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Fonts and CSS -->
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
            max-width: 600px;
        }
    </style>
</head>

<body id="kt_body" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-column flex-column-fluid justify-content-center align-items-center min-vh-100 p-12">

            <div class="bg-body rounded-4 p-20 auth-card">
                <div class="d-flex flex-column align-items-center w-100 px-lg-10">

                    <form method="POST" action="{{ route('login') }}" class="form w-100" novalidate>
                        @csrf

                        <div class="text-center mb-11">
                            <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
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

                        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                            <label class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                    {{ old('remember') ? 'checked' : '' }} />
                                <span class="form-check-label" for="remember">Remember me</span>
                            </label>

                            <a href="#" class="link-primary">Forgot Password ?</a>
                        </div>

                        <div class="d-grid mb-10">
                            <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                                <span class="indicator-label">Sign In</span>
                                <span class="indicator-progress">Please wait...
                                    <span
                                        class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/custom/authentication/sign-in/general.js') }}"></script>
</body>

</html>