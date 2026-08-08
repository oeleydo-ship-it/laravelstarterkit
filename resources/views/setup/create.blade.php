<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set up {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; }
        .setup-card { max-width: 540px; border: 0; border-radius: 1rem; box-shadow: 0 1rem 3rem rgba(15, 23, 42, .12); }
    </style>
</head>
<body>
<main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="card setup-card w-100">
        <div class="card-body p-4 p-md-5">
            <div class="text-primary fw-semibold mb-2">First-time setup</div>
            <h1 class="h3 mb-2">Create your super administrator</h1>
            <p class="text-secondary mb-4">This account controls the entire installation. Use an email address and password that you keep secure.</p>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('setup.store') }}">
                @csrf
                <input type="hidden" name="setup_token" value="{{ $setupToken }}">
                <div class="mb-3">
                    <label for="name" class="form-label">Full name</label>
                    <input id="name" name="name" class="form-control" value="{{ old('name') }}" required autofocus autocomplete="name">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" required autocomplete="username">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" name="password" type="password" class="form-control" required minlength="12" autocomplete="new-password">
                    <div class="form-text">Use at least 12 characters.</div>
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required minlength="12" autocomplete="new-password">
                </div>
                <button class="btn btn-primary btn-lg w-100" type="submit">Complete setup</button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
