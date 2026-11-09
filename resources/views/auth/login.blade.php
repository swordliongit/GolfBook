<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in · GolfBook</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-page">
<form method="POST" action="{{ url('login') }}" class="card login-card">
    @csrf
    <h1>⛳ GolfBook</h1>
    <p class="muted">Operations panel</p>

    <label for="email">E-mail</label>
    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

    <label for="password">Password</label>
    <input id="password" name="password" type="password" required>

    <label class="inline"><input type="checkbox" name="remember"> Remember me</label>

    @error('email')
        <div class="flash error">{{ $message }}</div>
    @enderror

    <button type="submit" class="btn primary">Log in</button>
</form>
</body>
</html>
