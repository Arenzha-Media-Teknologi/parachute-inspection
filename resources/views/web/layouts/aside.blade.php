@php

$permission = json_decode(Auth::user()->user_groups->permissions);

@endphp

<div id="kt_aside" class="aside aside-dark aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_mobile_toggle">
    <!--begin::Brand-->
    <div class="aside-logo flex-column-auto" id="kt_aside_logo">
        <!--begin::Logo-->
        <a href="index.html">
            <img alt="Logo" src="{{ asset('assets/media/logos/logo-pilt.png') }}" class="ms-5 h-50px logo" />
        </a>
        <!--end::Logo-->
        <!--begin::Aside toggler-->
        <div id="kt_aside_toggle" class="btn btn-icon w-auto px-0 btn-active-color-primary aside-toggle me-n2" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="aside-minimize">
            <i class="ki-outline ki-double-left fs-1 rotate-180"></i>
        </div>
        <!--end::Aside toggler-->
    </div>
    <!--end::Brand-->
    <!--begin::Aside menu-->
    <div class="aside-menu flex-column-fluid">
        <!--begin::Aside Menu-->
        <div class="hover-scroll-overlay-y" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer" data-kt-scroll-wrappers="#kt_aside_menu" data-kt-scroll-offset="0">
            <!--begin::Menu-->
            <div class="menu menu-column menu-title-gray-800 menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500" id="#kt_aside_menu" data-kt-menu="true">

                <div class="menu-item pt-5">
                    <div class="menu-content">
                        <span class="menu-heading fw-bold text-uppercase fs-7">Menu</span>
                    </div>
                </div>

                <div class="menu-item">
                    <a class="menu-link {{ request()->is('/*') ? 'active' : '' }}" href="/">
                        <span class="menu-icon">
                            <i class="ki-outline ki-rocket fs-2"></i>
                        </span>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </div>

                @if(in_array("view_parachute", $permission))
                <div class="menu-item">
                    <a class="menu-link {{ request()->is('parachute') ? 'active' : '' }}" href="/parachute">
                        <span class="menu-icon">
                            <i class="ki-outline ki-airplane-square fs-2"></i>
                        </span>
                        <span class="menu-title">Parasut</span>
                    </a>
                </div>
                @endif

                @if(in_array("view_parachute_check", $permission))
                <div class="menu-item">
                    <a class="menu-link {{ request()->is('parachute-inspection*') ? 'active' : '' }}" href="/parachute-inspection">
                        <span class="menu-icon">
                            <i class="ki-outline ki-shield-tick fs-2"></i>
                        </span>
                        <span class="menu-title">Pemeriksaan Parasut</span>
                    </a>
                </div>
                @endif

                @if(in_array("view_user_group", $permission))
                <div class="menu-item">
                    <a class="menu-link {{ request()->is('group*') ? 'active' : '' }}" href="/group">
                        <span class="menu-icon">
                            <i class="ki-outline ki-profile-user fs-2"></i>
                        </span>
                        <span class="menu-title">User Group</span>
                    </a>
                </div>
                @endif

                @if(in_array("view_user", $permission))
                <div class="menu-item">
                    <a class="menu-link {{ request()->is('user*') ? 'active' : '' }}" href="/user">
                        <span class="menu-icon">
                            <i class="ki-outline ki-user-square fs-2"></i>
                        </span>
                        <span class="menu-title">User</span>
                    </a>
                </div>
                @endif


                <div class="menu-item">
                    <a class="menu-link {{ request()->is('backup*') ? 'active' : '' }}" href="/backup">
                        <span class="menu-icon">
                            <i class="ki-outline ki-shield fs-2"></i>
                        </span>
                        <span class="menu-title">Backup</span>
                    </a>
                </div>




            </div>
        </div>
    </div>
    <!--end::Aside menu-->
</div>