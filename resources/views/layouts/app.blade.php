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

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show">
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('import_csv'))
            <div class="mb-2">
                <a class="btn btn-sm btn-outline-dark"
                href="{{ route('rndc.manifiestos.import_result', ['path' => session('import_csv')]) }}">
                    ⬇️ Descargar resultado CSV
                </a>
            </div>
        @endif

        @if(session('import_details'))
            <div class="card mb-3">
                <div class="card-header py-2">
                    <strong>Detalle importación (primeras {{ count(session('import_details')) }} filas)</strong>
                    <div class="text-muted small">Descarga el CSV para ver todo.</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fila</th>
                                    <th>Manifiesto</th>
                                    <th>Autorización</th>
                                    <th>Estado</th>
                                    <th>Mensaje</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach(session('import_details') as $d)
                                <tr>
                                    <td>{{ $d['fila'] }}</td>
                                    <td>{{ $d['manifiesto'] }}</td>
                                    <td>{{ $d['autorizacion'] }}</td>
                                    <td>
                                        @if($d['estado'] === 'ok')
                                            <span class="badge text-bg-success">OK</span>
                                        @elseif($d['estado'] === 'skipped')
                                            <span class="badge text-bg-secondary">Omitido</span>
                                        @else
                                            <span class="badge text-bg-danger">Falló</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $d['mensaje'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
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
