<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js'])
    @inertiaHead
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
<body data-theme="light" class="">
@inertia
<!-- Javascript -->
<script src="{{url('/')}}/assets/bundles/libscripts.bundle.js"></script>
<script src="{{url('/')}}/assets/bundles/vendorscripts.bundle.js"></script>

<!-- page vendor js file -->
<script src="{{url('/')}}/assets/vendor/toastr/toastr.js"></script>
<script src="{{url('/')}}/assets/bundles/c3.bundle.js"></script>

<!-- page js file -->
<script src="{{url('/')}}/assets/bundles/mainscripts.bundle.js"></script>



</body>
</html>

