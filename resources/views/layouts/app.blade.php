<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Monitoreo RNDC')</title>

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Estilos opcionales --}}
    <style>
        body {
            background: #f5f6f8;
        }
        .navbar-brand {
            font-weight: bold;
        }
        footer {
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .pagination .page-item .page-link svg {
            width: 14px !important;
            height: 14px !important;
        }
        .pagination .page-link {
            padding: 6px 12px !important;
            font-size: 0.85rem !important;
        }
        .pagination .page-link svg {
            width: 14px !important;
            height: 14px !important;
        }
    </style>

    @stack('styles')
</head>
<body>
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/') }}">
                Monitoreo RNDC
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav ms-auto">

                    {{-- Pendientes --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('rndc.manifiestos.index') ? 'active fw-semibold' : '' }}"
                        href="{{ route('rndc.manifiestos.index') }}">
                            Pendientes
                        </a>
                    </li>

                    {{-- Procesados / Histórico --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('rndc.manifiestos.procesados.*') ? 'active fw-semibold' : '' }}"
                        href="{{ route('rndc.manifiestos.procesados.index') }}">
                            Procesados
                        </a>
                    </li>

                </ul>

            </div>
        </div>
    </nav>

    {{-- Mensajes Flash --}}
    <div class="container mt-3">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Errores de validación --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Revisa los campos:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Contenido principal --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer>
        Monitoreo RNDC — {{ date('Y') }}
    </footer>

    {{-- JS Bootstrap --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>
