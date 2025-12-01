@extends('layouts.app')

@section('title', 'Detalle Manifiesto RNDC')

@section('content')
<div class="container mt-4">

    <h1 class="h3 mb-3">Detalle Manifiesto</h1>

    <div class="mb-3">
        <a href="{{ route('rndc.manifiestos.index') }}" class="btn btn-sm btn-outline-secondary">
            &laquo; Volver al listado
        </a>
    </div>

    {{-- Datos principales --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">
                Manifiesto {{ $manifiesto->nummanifiestocarga }}
            </h5>

            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>Ingreso ID:</strong> {{ $manifiesto->ingresoidmanifiesto }}
                </div>
                <div class="col-md-4">
                    <strong>Placa:</strong> {{ $manifiesto->numplaca }}
                </div>
                <div class="col-md-4">
                    <strong>Empresa:</strong> {{ $manifiesto->codigoempresa }}
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4">
                    <strong>NIT Empresa Transp:</strong> {{ $manifiesto->numnitempresatransporte }}
                </div>
                <div class="col-md-4">
                    <strong>Fecha Expedición:</strong>
                    {{ optional($manifiesto->fechaexpedicionmanifiesto)->format('d/m/Y') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Puntos de Control --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Puntos de Control</h5>

            @if($manifiesto->puntosControl->isEmpty())
                <p class="text-muted mb-0">Este manifiesto no tiene puntos de control registrados.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Código Punto</th>
                                <th>Municipio</th>
                                <th>Dirección</th>
                                <th>Fecha / Hora Cita</th>
                                <th>Lat / Long</th>
                                <th>Tiempo pactado</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($manifiesto->puntosControl as $pc)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $pc->codpuntocontrol }}</td>
                                <td>{{ $pc->codmunicipio }}</td>
                                <td>{{ $pc->direccion }}</td>
                                <td>
                                    {{ optional($pc->fechacita)->format('d/m/Y') }}<br>
                                    {{ $pc->horacita }}
                                </td>
                                <td>
                                    Lat: {{ $pc->latitud }}<br>
                                    Long: {{ $pc->longitud }}
                                </td>
                                <td>{{ $pc->tiempopactado }} min</td>
                                <td>
                                    @if($pc->finalizado)
                                        <span class="badge bg-success">Finalizado</span><br>
                                        @if($pc->numero_autorizacion)
                                            <small class="text-muted">
                                                Autorización: {{ $pc->numero_autorizacion }}<br>
                                                {{ optional($pc->evento_enviado_at)->format('d/m/Y H:i') }}
                                            </small>
                                        @endif
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$pc->finalizado)
                                        <a href="{{ route('rndc.puntos.evento.create', [$manifiesto, $pc]) }}"
                                           class="btn btn-sm btn-primary">
                                            Registrar evento
                                        </a>
                                    @else
                                        <button class="btn btn-sm btn-outline-secondary" disabled>
                                            Evento enviado
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
