<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link href="{{ asset('vendor/tabler/dist/css/tabler.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/tabler/dist/css/tabler-flags.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/tabler/dist/css/tabler-payments.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/tabler/dist/css/tabler-vendors.min.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <script src="{{ asset('vendor/tabler/dist/js/demo-theme.min.js') }}"></script>
    <div class="page">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <div class="page-wrapper">
            <!-- Page header -->
            @hasSection('page-title')
                @include('layouts.partials.page-header')
            @endif

            <!-- Page body -->
            <div class="page-body">
                <div class="container-xl">
                    @yield('content')
                </div>
            </div>

            @include('layouts.partials.footer')
        </div>
    </div>

    @if (session('status'))
        <div data-flash="{{ session('status') }}" hidden></div>
    @endif

    @stack('modals')
    <script src="{{ asset('vendor/tabler/dist/js/tabler.min.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
