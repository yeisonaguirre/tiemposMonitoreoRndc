@extends('layouts.app')
@section('title', 'Manifiestos RNDC - Procesados')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 m-0">RNDC — Procesados</h1>
            <div class="small text-muted">Histórico de puntos con evento enviado</div>
        </div>
        <a href="{{ route('rndc.manifiestos.index') }}" class="btn btn-sm btn-outline-secondary">
            Ver pendientes
        </a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-3 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Ingreso</label>
                    <input class="form-control form-control-sm" name="ingresoidmanifiesto" value="{{ request('ingresoidmanifiesto') }}">
                </div>
                <div class="col-12 col-md-3 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Manifiesto</label>
                    <input class="form-control form-control-sm" name="nummanifiestocarga" value="{{ request('nummanifiestocarga') }}">
                </div>
                <div class="col-12 col-md-2 col-lg-1">
                    <label class="form-label form-label-sm mb-1">Placa</label>
                    <input class="form-control form-control-sm" name="numplaca" value="{{ request('numplaca') }}">
                </div>
                <div class="col-12 col-md-2 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Empresa</label>
                    <input class="form-control form-control-sm" name="codigoempresa" value="{{ request('codigoempresa') }}">
                </div>
                <div class="col-12 col-md-2 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Enviado desde</label>
                    <input class="form-control form-control-sm" name="enviado_desde" placeholder="dd/mm/aaaa" value="{{ request('enviado_desde') }}">
                </div>
                <div class="col-12 col-md-2 col-lg-2">
                    <label class="form-label form-label-sm mb-1">Enviado hasta</label>
                    <input class="form-control form-control-sm" name="enviado_hasta" placeholder="dd/mm/aaaa" value="{{ request('enviado_hasta') }}">
                </div>

                <div class="col-12 col-lg-1 d-grid">
                    <button class="btn btn-sm btn-secondary">Buscar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive" style="max-height: calc(100vh - 300px); overflow:auto;">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-dark" style="position:sticky; top:0; z-index:2;">
                    <tr>
                        <th>Manifiesto</th>
                        <th>Placa</th>
                        <th>Empresa</th>
                        <th>Último envío</th>
                        <th style="min-width:520px;">Log puntos finalizados</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($manifiestos as $m)
                    <tr>
                        <td data-label="Manifiesto">
                            <a class="text-decoration-none" href="{{ route('rndc.manifiestos.show', $m) }}">
                                {{ $m->nummanifiestocarga }}
                            </a>
                            <div class="small text-muted">Ingreso: {{ $m->ingresoidmanifiesto }}</div>
                        </td>
                        <td data-label="Placa"><span class="badge text-bg-light">{{ $m->numplaca }}</span></td>
                        <td data-label="Empresa"><span class="badge text-bg-secondary">{{ $m->codigoempresa }}</span></td>
                        <td data-label="Último envío">{{ $m->ultima_fecha_evento ? \Carbon\Carbon::parse($m->ultima_fecha_evento)->format('d/m/Y H:i') : '—' }}</td>

                        <td data-label="Log puntos finalizados">
                            <div style="max-height: 220px; overflow:auto; padding-right:.25rem;">
                                @foreach($m->puntosControl as $pc)
                                    <div class="border rounded-3 p-2 mb-2 bg-white">
                                        <div class="d-flex justify-content-between gap-2">
                                            <div>
                                                <div class="fw-semibold">
                                                    Punto {{ $pc->codpuntocontrol }}
                                                    <span class="text-muted fw-normal">({{ $pc->codmunicipio }})</span>
                                                </div>
                                                <div class="small text-muted">{{ $pc->direccion }}</div>
                                                <div class="small text-muted">
                                                    Autorización: {{ $pc->numero_autorizacion ?? '—' }}
                                                    <span class="mx-1">•</span>
                                                    Enviado: {{ optional($pc->evento_enviado_at)->format('d/m/Y H:i') ?? '—' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No hay registros procesados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $manifiestos->links() }}
    </div>
</div>
@endsection
