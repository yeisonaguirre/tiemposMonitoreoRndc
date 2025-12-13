@extends('layouts.app')

@section('title', 'Detalle Manifiesto RNDC')

@section('content')
<div class="container-fluid mt-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 m-0">Detalle Manifiesto</h1>
            <div class="text-muted small">Manifiesto: <strong>{{ $manifiesto->nummanifiestocarga }}</strong></div>
        </div>

        <a href="{{ route('rndc.manifiestos.index') }}" class="btn btn-sm btn-outline-secondary">
            &laquo; Volver
        </a>
    </div>

    {{-- ✅ Datos principales en “cards” --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge text-bg-dark">Ingreso: {{ $manifiesto->ingresoidmanifiesto }}</span>
                        <span class="badge text-bg-secondary">Empresa: {{ $manifiesto->codigoempresa }}</span>
                        <span class="badge text-bg-light">Placa: {{ $manifiesto->numplaca }}</span>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">NIT Empresa Transp</div>
                            <div class="fw-semibold">{{ $manifiesto->numnitempresatransporte }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">Fecha expedición</div>
                            <div class="fw-semibold">
                                {{ optional($manifiesto->fechaexpedicionmanifiesto)->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ✅ Resumen puntos --}}
        <div class="col-12 col-lg-4">
            @php
                $total = $manifiesto->puntosControl->count();
                $finalizados = $manifiesto->puntosControl->where('finalizado', true)->count();
                $pendientes = $total - $finalizados;
            @endphp

            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-2">Resumen</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary">Total: {{ $total }}</span>
                        <span class="badge bg-success">Finalizados: {{ $finalizados }}</span>
                        <span class="badge bg-warning text-dark">Pendientes: {{ $pendientes }}</span>
                    </div>

                    <hr>

                    <div class="small text-muted">
                        Consejo: en móvil es más fácil ver puntos en acordeón.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ Puntos de Control --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="card-title m-0">Puntos de Control</h5>
            </div>

            @if($manifiesto->puntosControl->isEmpty())
                <p class="text-muted mb-0">Este manifiesto no tiene puntos de control registrados.</p>
            @else
                <div class="accordion" id="accordionPuntos">
                    @foreach($manifiesto->puntosControl as $pc)
                        @php
                            $isFinal = (bool) $pc->finalizado;
                            $headingId = "heading{$pc->id}";
                            $collapseId = "collapse{$pc->id}";
                        @endphp

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="{{ $headingId }}">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $collapseId }}"
                                        aria-expanded="false"
                                        aria-controls="{{ $collapseId }}">
                                    <div class="d-flex flex-wrap align-items-center gap-2 w-100">
                                        <span class="fw-semibold">Punto {{ $pc->codpuntocontrol }}</span>
                                        <span class="text-muted">({{ $pc->codmunicipio }})</span>

                                        <span class="ms-auto">
                                            @if($isFinal)
                                                <span class="badge bg-success">Finalizado</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            @endif
                                        </span>
                                    </div>
                                </button>
                            </h2>

                            <div id="{{ $collapseId }}" class="accordion-collapse collapse show"
                                 aria-labelledby="{{ $headingId }}"
                                 data-bs-parent="#accordionPuntos">
                                <div class="accordion-body">

                                    <div class="row g-3">
                                        <div class="col-12 col-lg-8">
                                            <div class="row g-2">
                                                <div class="col-12 col-md-6">
                                                    <div class="small text-muted">Dirección</div>
                                                    <div class="fw-semibold">{{ $pc->direccion }}</div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="small text-muted">Fecha / Hora cita</div>
                                                    <div class="fw-semibold">
                                                        {{ optional($pc->fechacita)->format('d/m/Y') ?? '—' }}
                                                        {{ $pc->horacita ?? '' }}
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="small text-muted">Lat / Long</div>
                                                    <div class="fw-semibold">
                                                        {{ $pc->latitud }} — {{ $pc->longitud }}
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="small text-muted">Tiempo pactado</div>
                                                    <div class="fw-semibold">{{ $pc->tiempopactado }} min</div>
                                                </div>

                                                @if($isFinal && $pc->numero_autorizacion)
                                                    <div class="col-12">
                                                        <div class="small text-muted">Autorización / Enviado</div>
                                                        <div class="fw-semibold">
                                                            {{ $pc->numero_autorizacion }}
                                                            <span class="text-muted fw-normal">
                                                                — {{ optional($pc->evento_enviado_at)->format('d/m/Y H:i') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-4">
                                            <div class="d-grid gap-2">
                                                @if(!$isFinal)
                                                    <a href="{{ route('rndc.puntos.evento.create', [$manifiesto, $pc]) }}"
                                                       class="btn btn-primary">
                                                        Registrar evento
                                                    </a>
                                                @else
                                                    <button class="btn btn-outline-secondary" disabled>
                                                        Evento enviado
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
