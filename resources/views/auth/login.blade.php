<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Mowria Group HRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #f3f5f9;
        }

        .login-wrap {
            min-height: 100vh;
            display: flex;
        }

        /* Left branding panel */
        .login-brand {
            flex: 1;
            display: none;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            color: #fff;
            background:
                radial-gradient(circle at 20% 20%, rgba(123,143,240,.35), transparent 45%),
                radial-gradient(circle at 80% 80%, rgba(52,195,143,.25), transparent 45%),
                linear-gradient(145deg, #1a2332 0%, #243b6b 100%);
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 992px) {
            .login-brand { display: flex; }
        }

        .login-brand .logo-badge {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #5b73e8, #7b8ff0);
            margin-bottom: 1.5rem;
        }

        .login-brand h2 {
            font-weight: 700;
            font-size: 2rem;
        }

        .login-brand p {
            color: rgba(255,255,255,.7);
            max-width: 420px;
        }

        .brand-features {
            margin-top: 2rem;
            display: grid;
            gap: .9rem;
        }

        .brand-feature {
            display: flex;
            align-items: center;
            gap: .8rem;
            color: rgba(255,255,255,.85);
            font-size: .95rem;
        }

        .brand-feature i {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: rgba(255,255,255,.12);
        }

        /* Right form panel */
        .login-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }

        .login-card .logo {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            font-size: 1.3rem;
            color: #fff;
            background: linear-gradient(135deg, #5b73e8, #7b8ff0);
            margin-bottom: 1.25rem;
        }

        .login-card h4 {
            font-weight: 700;
            margin-bottom: .25rem;
        }

        .login-card .subtitle {
            color: #98a0ac;
            font-size: .9rem;
            margin-bottom: 1.75rem;
        }

        .form-control {
            border-radius: 10px;
            padding: .65rem .9rem;
            border-color: #e3e6ec;
        }

        .form-control:focus {
            border-color: #5b73e8;
            box-shadow: 0 0 0 .2rem rgba(91,115,232,.15);
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: #fff;
            border-color: #e3e6ec;
            border-right: none;
            color: #98a0ac;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .btn-login {
            background: linear-gradient(135deg, #5b73e8, #7b8ff0);
            border: none;
            border-radius: 10px;
            padding: .7rem;
            font-weight: 600;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #4a63d8, #6a80e0);
            color: #fff;
        }

        .password-toggle {
            cursor: pointer;
            border-radius: 0 10px 10px 0 !important;
            border-left: none !important;
        }
    </style>
</head>
<body>
    <div class="login-wrap">
        <!-- Branding panel -->
        <div class="login-brand">
            <div class="logo-badge"><i class="fas fa-building"></i></div>
            <h2>Mowria Group HRM</h2>
            <p>Manage your workforce with ease — employees, attendance, leave requests and analytics in one place.</p>

            <div class="brand-features">
                <div class="brand-feature"><i class="fas fa-users"></i> Employee management &amp; profiles</div>
                <div class="brand-feature"><i class="fas fa-clock"></i> Real-time attendance tracking</div>
                <div class="brand-feature"><i class="fas fa-plane-departure"></i> Leave requests &amp; approvals</div>
                <div class="brand-feature"><i class="fas fa-chart-line"></i> Analytics &amp; reporting</div>
            </div>
        </div>

        <!-- Form panel -->
        <div class="login-panel">
            <div class="login-card">
                <div class="logo"><i class="fas fa-building"></i></div>
                <h4>Welcome back</h4>
                <div class="subtitle">Sign in to your account to continue</div>

                @if($errors->any())
                    <div class="alert alert-danger py-2">
                        <i class="fas fa-exclamation-circle me-1"></i> {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email"
                                   value="{{ old('email') }}"
                                   placeholder="you@company.com"
                                   required autocomplete="email" autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password"
                                   placeholder="Enter your password"
                                   required autocomplete="current-password">
                            <span class="input-group-text password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="passwordIcon"></i>
                            </span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label small" for="remember">Remember me</label>
                        </div>
                        <a href="#" class="small text-decoration-none" style="color:#5b73e8;">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-login btn-primary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i> Sign In
                    </button>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Secured access for Mowria Group employees only
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
        }
    </script>
</body>
</html>
