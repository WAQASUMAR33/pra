<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PRA e-IMS Integration Dashboard')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo-section">
                <div class="logo-icon">PR</div>
                <div class="logo-text">PRA Portal</div>
            </div>
            
            <nav style="flex: 1;">
                <ul class="nav-links">
                    <li class="nav-item {{ Route::is('dashboard') ? 'active' : '' }}">
                        <a href="{{ route('dashboard') }}">
                            📊 Dashboard
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('invoices.create') ? 'active' : '' }}">
                        <a href="{{ route('invoices.create') }}">
                            📝 Create Invoice
                        </a>
                    </li>
                    <li class="nav-item {{ Route::is('settings') ? 'active' : '' }}">
                        <a href="{{ route('settings') }}">
                            ⚙️ POS Settings
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div style="color: var(--text-muted); font-size: 0.8rem; text-align: center; border-top: 1px solid var(--panel-border); padding-top: 1rem;">
                v1.0.0 &bull; PRAL e-IMS
            </div>
        </aside>
        
        <div class="main-wrapper">
            @yield('content')
        </div>
    </div>
</body>
</html>
