<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', '3DHub') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <!-- Custom Favicon Placeholder -->
    <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon/sabah-logo.png') }}">
</head>
<body>
    @auth
        @if(Auth::user()->role === 'admin')
            <div class="sidebar">
                <div class="logo" style="margin-bottom: 20px; text-align: center;">
                    <span style="font-size: 1.8rem; font-weight: 700; background: linear-gradient(to right, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">SABAH 3DHUB</span>
                </div>
                
                <div style="padding: 0 20px 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px; text-align: center;">
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="var(--text-dim)" viewBox="0 0 256 256"><path d="M128,24a104,104,0,1,0,104,104A104.11,104.11,0,0,0,128,24ZM74.08,197.5a64,64,0,0,1,107.84,0,87.83,87.83,0,0,1-107.84,0ZM128,144a40,40,0,1,1,40-40A40,40,0,0,1,128,144Zm43.55,42.5a80,80,0,0,0-87.1,0,88,88,0,1,1,87.1,0Z"></path></svg>
                    </div>
                    <div style="font-weight: 600; font-size: 0.95rem; color: var(--text);">{{ Auth::user()->name }}</div>
                    <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: var(--primary); font-size: 0.65rem; margin-top: 4px; display: inline-block;">SYSTEM ADMIN</span>
                </div>

                <div class="sidebar-links">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160ZM240,128c0,42.52-25.06,79.13-64,103.11v-34.9a8,8,0,0,0-16,0v49a8,8,0,0,0,12,6.93c44.88-25.92,72-68.58,72-117.1a108.13,108.13,0,0,0-108-108h-2l16.12,16.12a8,8,0,0,1-11.31,11.31l-32-32a8,8,0,0,1,0-11.31l32-32a8,8,0,0,1,11.31,11.31L139.12,28.23l2.88,0A124.14,124.14,0,0,1,240,128ZM32,48V6.15A8,8,0,0,1,44,.1l32,32a8,8,0,0,1,0,11.31l-32,32A8,8,0,0,1,32,69.54V48A124.14,124.14,0,0,1,132.88,28.23L135.76,28h2a108.13,108.13,0,0,1,108,108c0,48.52-27.12,91.18-72,117.1a8,8,0,0,1-12-6.93v-49a8,8,0,0,1,16,0v34.9c38.94-23.98,64-60.59,64-103.11a124.14,124.14,0,0,0-107.12-124l-2.88-.23L116.82,23.6a8,8,0,1,1-11.31-11.31l32-32a8,8,0,0,1,11.31,0l5.86,5.86Z" opacity="0"></path><path d="M216,40V216a8,8,0,0,1-8,8H48a8,8,0,0,1-8-8V40a8,8,0,0,1,8-8H208A8,8,0,0,1,216,40ZM128,152a24,24,0,1,0-24-24A24,24,0,0,0,128,152Z"></path></svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M128,24a104,104,0,1,0,104,104A104.11,104.11,0,0,0,128,24ZM74.08,197.5a64,64,0,0,1,107.84,0,87.83,87.83,0,0,1-107.84,0ZM128,144a40,40,0,1,1,40-40A40,40,0,0,1,128,144Zm43.55,42.5a80,80,0,0,0-87.1,0,88,88,0,1,1,87.1,0Z"></path></svg>
                        Users
                    </a>
                    <a href="{{ route('admin.submissions') }}" class="{{ request()->routeIs('admin.submissions') ? 'active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M200,32H56A16,16,0,0,0,40,48V208a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V48A16,16,0,0,0,200,32Zm0,176H56V48H200V208ZM184,128a8,8,0,0,1-8,8H80a8,8,0,0,1,0-16h96A8,8,0,0,1,184,128Zm0,32a8,8,0,0,1-8,8H80a8,8,0,0,1,0-16h96A8,8,0,0,1,184,160Zm0,32a8,8,0,0,1-8,8H80a8,8,0,0,1,0-16h96A8,8,0,0,1,184,192ZM184,96a8,8,0,0,1-8,8H80a8,8,0,0,1,0-16h96A8,8,0,0,1,184,96Z"></path></svg>
                        Submissions
                    </a>
                </div>
                <div style="margin-top: auto; padding: 20px;">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger" style="padding: 10px; font-size: 0.9rem;">Logout</button>
                    </form>
                </div>
            </div>
        @else
            <nav class="nav-bar">
                <div class="logo">
                    <a href="{{ route('dashboard') }}" style="text-decoration: none; color: white; display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.5rem; font-weight: 700; background: linear-gradient(to right, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">SABAH 3DHUB</span>
                    </a>
                </div>
                <div class="nav-links" style="display: flex; align-items: center; gap: 24px;">
                    <span style="color: var(--text-dim); font-size: 0.9rem;">Logged in as: <strong style="color: var(--text);">{{ Auth::user()->name }}</strong> ({{ ucfirst(Auth::user()->role) }})</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" style="background: none; border: 1px solid var(--border); color: #ef4444; padding: 6px 14px; border-radius: 8px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'" onmouseout="this.style.background='none'">Logout</button>
                    </form>
                </div>
            </nav>
        @endif
    @endauth

    <div class="{{ Auth::check() && Auth::user()->role === 'admin' ? 'admin-layout' : '' }}">
        <main class="{{ Auth::check() ? (Auth::user()->role === 'admin' ? 'admin-main' : 'main-content') : 'auth-container' }}">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-error">
                    <ul style="list-style: none; padding: 0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <footer style="text-align: center; padding: 40px; color: var(--text-dim); font-size: 0.8rem;">
        &copy; {{ date('Y') }} Sabah 3DHub.
    </footer>
    <script>
        // Auto-hide alerts after 4 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 600);
            });
        }, 4000);
    </script>
    @stack('scripts')
</body>
</html>
