<!doctype html>
<html lang="en">

<head>
    <title>Connexion - {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Iconic Bootstrap 4.5.0 Admin Template">
    <meta name="author" content="WrapTheme, design by: ThemeMakker.com">

    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <!-- VENDOR CSS -->
    <link rel="stylesheet" href="{{url('/')}}/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{url('/')}}/assets/vendor/font-awesome/css/font-awesome.min.css">

    <!-- MAIN CSS -->
    <link rel="stylesheet" href="{{url('/')}}/assets/css/main.css">

</head>

<body data-theme="light" class="font-nunito">
<!-- WRAPPER -->
<div id="wrapper" class="theme-cyan">
    <div class="vertical-align-wrap">
        <div class="vertical-align-middle auth-main">
            <div class="auth-box">
                <div class="top">
                    <img src="assets/images/logo-white.svg" alt="Iconic">
                </div>
                @inertia
            </div>
        </div>
    </div>
</div>
<!-- END WRAPPER -->
</body>
</html>
