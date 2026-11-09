<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Operations') · GolfBook</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<header class="topbar">
    <a href="{{ route('admin.dashboard') }}" class="brand">⛳ GolfBook <span>Operations</span></a>
    <nav>
        <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Today</a>
        <a href="{{ route('admin.tee-sheet') }}" @class(['active' => request()->routeIs('admin.tee-sheet')])>Tee sheet</a>
        <a href="{{ route('admin.bookings.index') }}" @class(['active' => request()->routeIs('admin.bookings.*')])>Bookings</a>
    </nav>
    <form method="POST" action="{{ route('logout') }}" class="logout">
        @csrf
        <span>{{ auth()->user()->name }}</span>
        <button type="submit" class="link">Log out</button>
    </form>
</header>

<main class="container">
    @if (session('status'))
        <div class="flash success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    @yield('content')
</main>
</body>
</html>
