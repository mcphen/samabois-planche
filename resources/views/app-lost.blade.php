<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>{{ config('app.name', 'Admin Panel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Iconic Bootstrap 4.5.0 Admin Template">
    <meta name="author" content="WrapTheme, design by: ThemeMakker.com">
    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <!-- VENDOR CSS -->
    <link rel="stylesheet" href="{{url('/')}}/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{url('/')}}/assets/vendor/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{url('/')}}/assets/vendor/toastr/toastr.min.css">
    <link rel="stylesheet" href="{{url('/')}}/assets/vendor/charts-c3/plugin.css"/>

    <!-- MAIN Project CSS file -->
    <link rel="stylesheet" href="{{url('/')}}/assets/css/main.css">
</head>
<body data-theme="light" class="font-nunito">
<div id="wrapper" class="theme-cyan">

    <!-- Page Loader -->
    <div class="page-loader-wrapper">
        <div class="loader">
            <div class="m-t-30"><img src="{{url('/')}}/assets/images/logo-icon.svg" width="48" height="48" alt="Iconic"></div>
            <p>Please wait...</p>
        </div>
    </div>

    <!-- Top navbar div start -->
    <nav class="navbar navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-brand">
                <button type="button" class="btn-toggle-offcanvas"><i class="fa fa-bars"></i></button>
                <button type="button" class="btn-toggle-fullwidth"><i class="fa fa-bars"></i></button>
                <a href="index.html">ICONIC</a>
            </div>

            <div class="navbar-right">
                <form id="navbar-search" class="navbar-form search-form">
                    <input value="" class="form-control" placeholder="Search here..." type="text">
                    <button type="button" class="btn btn-default"><i class="icon-magnifier"></i></button>
                </form>

                <div id="navbar-menu">
                    <ul class="nav navbar-nav">
                        <li class="dropdown">
                            <a href="javascript:void(0);" class="dropdown-toggle icon-menu" data-toggle="dropdown">
                                <i class="fa fa-bell"></i>
                                <span class="notification-dot"></span>
                            </a>
                            <ul class="dropdown-menu notifications">
                                <li class="header"><strong>You have 4 new Notifications</strong></li>
                                <li>
                                    <a href="javascript:void(0);">
                                        <div class="media">
                                            <div class="media-left">
                                                <i class="icon-info text-warning"></i>
                                            </div>
                                            <div class="media-body">
                                                <p class="text">Campaign <strong>Holiday Sale</strong> is nearly reach budget limit.</p>
                                                <span class="timestamp">10:00 AM Today</span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);">
                                        <div class="media">
                                            <div class="media-left">
                                                <i class="icon-like text-success"></i>
                                            </div>
                                            <div class="media-body">
                                                <p class="text">Your New Campaign <strong>Holiday Sale</strong> is approved.</p>
                                                <span class="timestamp">11:30 AM Today</span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);">
                                        <div class="media">
                                            <div class="media-left">
                                                <i class="icon-pie-chart text-info"></i>
                                            </div>
                                            <div class="media-body">
                                                <p class="text">Website visits from Twitter is 27% higher than last week.</p>
                                                <span class="timestamp">04:00 PM Today</span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);">
                                        <div class="media">
                                            <div class="media-left">
                                                <i class="icon-info text-danger"></i>
                                            </div>
                                            <div class="media-body">
                                                <p class="text">Error on website analytics configurations</p>
                                                <span class="timestamp">Yesterday</span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li class="footer"><a href="javascript:void(0);" class="more">See all notifications</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="page-login.html" class="icon-menu"><i class="fa fa-power-off"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- main left menu -->
    <div id="left-sidebar" class="sidebar">
        <button type="button" class="btn-toggle-offcanvas"><i class="fa fa-arrow-left"></i></button>
        <div class="sidebar-scroll">
            <div class="user-account">
                <img src="{{url('/')}}/assets/images/user.png" class="rounded-circle user-photo" alt="User Profile Picture">
                <div class="dropdown">
                    <span>Welcome,</span>
                    <a href="javascript:void(0);" class="dropdown-toggle user-name" data-toggle="dropdown"><strong>Pamela Petrus</strong></a>
                    <ul class="dropdown-menu dropdown-menu-right account">
                        <li><a href="page-profile2.html"><i class="icon-user"></i>My Profile</a></li>
                        <li><a href="app-inbox.html"><i class="icon-envelope-open"></i>Messages</a></li>
                        <li><a href="javascript:void(0);"><i class="icon-settings"></i>Settings</a></li>
                        <li class="divider"></li>
                        <li><a href="page-login.html"><i class="icon-power"></i>Logout</a></li>
                    </ul>
                </div>

            </div>


            <!-- Tab panes -->
            <div class="tab-content padding-0">
                <div class="tab-pane active" id="menu">
                    <nav id="left-sidebar-nav" class="sidebar-nav">
                        <ul id="main-menu" class="metismenu li_animation_delay">
                            <li class="active">
                                <a href="#Dashboard" class="has-arrow"><i class="fa fa-dashboard"></i><span>Dashboard</span></a>
                                <ul>
                                    <li class="active"><a href="index.html">Analytical</a></li>
                                    <li><a href="h-menu.html">Analytical H-Menu</a></li>
                                    <li><a href="index9.html">IoT Dashboard</a></li>
                                    <li><a href="index2.html">Demographic</a></li>
                                    <li><a href="index6.html">Project Board</a></li>
                                    <li><a href="index7.html">Crypto Dashboard</a></li>
                                    <li><a href="index8.html">eCommerce</a></li>
                                </ul>
                            </li>

                        </ul>
                    </nav>
                </div>

            </div>
        </div>
    </div>



    <!-- mani page content body part -->
    <div id="main-content">
        <div class="container-fluid">
            @inertia

        </div>
    </div>

</div>
<!-- Javascript -->
<script src="{{url('/')}}/assets/bundles/libscripts.bundle.js"></script>
<script src="{{url('/')}}/assets/bundles/vendorscripts.bundle.js"></script>

<!-- page vendor js file -->
<script src="{{url('/')}}/assets/vendor/toastr/toastr.js"></script>
<script src="{{url('/')}}/assets/bundles/c3.bundle.js"></script>

<!-- page js file -->
<script src="{{url('/')}}/assets/bundles/mainscripts.bundle.js"></script>


<script>
    document.getElementById("menu-toggle").addEventListener("click", function(e) {
        e.preventDefault();
        document.getElementById("wrapper").classList.toggle("toggled");
    });
</script>
</body>
</html>
