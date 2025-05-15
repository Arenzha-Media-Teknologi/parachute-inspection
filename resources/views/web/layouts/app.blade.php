<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <title>@yield('title')</title>
    <meta charset="utf-8" />
    <meta name="description" content="Parachute Inspection Information System" />
    <meta name="keywords" content="parachute, inspection, information system" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="Parachute Inspection Information System" />
    <meta property="og:site_name" content="Parachute Inspection Information System" />
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />


    <!--end::Global Stylesheets Bundle-->
    @yield('prehead')
    @yield('head')
    <style>
        [v-cloak] {
            display: none
        }
    </style>
    @yield('pagestyle')
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>

    <div class="d-flex flex-column flex-root">
        <div class="page d-flex flex-row flex-column-fluid">
            @include('web.layouts.aside')
            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                @include('web.layouts.header')
                @yield('content')
                @include('web.layouts.footer')
            </div>
        </div>
    </div>
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-outline ki-arrow-up"></i>
    </div>
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <!--end::Global Javascript Bundle-->
    @if(env('APP_ENV') == 'local')
    <script src="{{ asset('assets/js/vue.js') }}"></script>
    @else
    <script src="{{ asset('assets/js/vue-prod.js') }}"></script>
    @endif
    @yield('script')
    @yield('pagescript')
    <!--end::Javascript-->
</body>
<!--end::Body-->

</html>