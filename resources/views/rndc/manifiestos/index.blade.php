@extends('layouts.app')

@section('title', 'Manifiestos RNDC')

@section('content')
<div class="container-fluid mt-4"> {{-- ✅ fluid para aprovechar ancho --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h1 class="h4 m-0">Man.RNDC</h1>

        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('rndc.manifiestos.sync') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    🔄 Consultar nuevos
                </button>
            </form>

            <button type="button" class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal" data-bs-target="#modalBuscarManifiesto">
                🔎 Buscar manifiesto
            </button>

            <button type="button" class="btn btn-sm btn-outline-success"
                    data-bs-toggle="modal" data-bs-target="#modalImportExcel">
                📥 Importar Excel
            </button>
        </div>

        {{-- Modal --}}
        <div class="modal fade" id="modalImportExcel" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                <form method="POST" action="{{ route('rndc.manifiestos.import_excel') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                    <h5 class="modal-title">Importar Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Archivo (.xlsx)</label>
                        <input type="file" name="archivo" class="form-control form-control-sm"
                            accept=".xlsx,.xls" required>
                        <div class="form-text">
                        Columnas: <b>manifiesto</b> y <b>autorizacion</b>
                        (o en ese orden).
                        </div>
                    </div>
                    </div>

                    <div class="modal-footer">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-sm btn-success" type="submit">Procesar</button>
                    </div>
                </form>
                </div>
            </div>
            </div>

        <div class="modal fade" id="modalBuscarManifiesto" tabindex="-1" aria-labelledby="modalBuscarManifiestoLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                <form method="POST" action="{{ route('rndc.manifiestos.sync_by_manifiesto') }}">
                    @csrf

                    <div class="modal-header">
                    <h5 class="modal-title" id="modalBuscarManifiestoLabel">Buscar manifiesto RNDC</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label form-label-sm">Nro Manifiesto</label>
                        <input type="text" name="nummanifiestocarga" class="form-control form-control-sm"
                            placeholder="Ej: 123456789" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label form-label-sm">Nro Autorización</label>
                        <input type="text" name="autorizacion" class="form-control form-control-sm"
                            placeholder="Ej: 987654321" required>
                    </div>
                    </div>

                    <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">🔄 Consultar</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ✅ Filtros en grid responsive --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('rndc.manifiestos.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label form-label-sm mb-1">Ingreso ID</label>
                        <input type="text" name="ingresoidmanifiesto" class="form-control form-control-sm"
                               value="{{ request('ingresoidmanifiesto') }}" placeholder="Ingreso ID">
                    </div>

                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label form-label-sm mb-1">Manifiesto</label>
                        <input type="text" name="nummanifiestocarga" class="form-control form-control-sm"
                               value="{{ request('nummanifiestocarga') }}" placeholder="Manifiesto">
                    </div>

                    <div class="col-12 col-md-2 col-lg-1">
                        <label class="form-label form-label-sm mb-1">Placa</label>
                        <input type="text" name="numplaca" class="form-control form-control-sm"
                               value="{{ request('numplaca') }}" placeholder="Placa">
                    </div>

                    <div class="col-12 col-md-2 col-lg-2">
                        <label class="form-label form-label-sm mb-1">Empresa</label>
                        <input type="text" name="numnitempresatransporte" class="form-control form-control-sm"
                               value="{{ request('numnitempresatransporte') }}" placeholder="Empresa">
                    </div>

                    <div class="col-12 col-md-2 col-lg-2">
                        <label class="form-label form-label-sm mb-1">Fecha Exp.</label>
                        <input type="text" name="fechaexpedicion" class="form-control form-control-sm"
                               value="{{ request('fechaexpedicion') }}" placeholder="dd/mm/aaaa">
                    </div>

                    <div class="col-12 col-lg-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-secondary flex-grow-1">
                                Buscar
                            </button>
                            <a href="{{ route('rndc.manifiestos.index') }}" class="btn btn-sm btn-outline-secondary">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Opcional: feedback de filtros activos --}}
                @if(request()->query())
                    <div class="mt-2 small text-muted">
                        Filtros activos: {{ collect(request()->query())->filter()->keys()->implode(', ') }}
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">

            {{-- ✅ wrapper con scroll horizontal --}}
            <div class="table-responsive rndc-table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark rndc-sticky">
                        <tr>
                            <th style="min-width:140px">Ingreso ID</th>
                            <th style="min-width:140px">Manifiesto</th>
                            <th style="min-width:90px">Placa</th>
                            <th style="min-width:110px">Fecha Exp.</th>
                            <th style="min-width:120px">Empresa</th>
                            <th style="min-width:520px">Puntos de Control / Evento</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse ($manifiestos as $m)
                        <tr>
                            <td data-label="Ingreso ID">
                                <a class="text-decoration-none" href="{{ route('rndc.manifiestos.show', $m) }}">
                                    {{ $m->ingresoidmanifiesto }}
                                </a>
                            </td>

                            <td data-label="Manifiesto">
                                <a class="text-decoration-none" href="{{ route('rndc.manifiestos.show', $m) }}">
                                    {{ $m->nummanifiestocarga }}
                                </a>
                            </td>

                            <td data-label="Placa"><span class="badge text-bg-light">{{ $m->numplaca }}</span></td>
                            <td data-label="Fecha Exp.">{{ optional($m->fechaexpedicionmanifiesto)->format('d/m/Y') }}</td>
                            <td data-label="Empresa"><span class="badge text-bg-secondary">{{ $m->numnitempresatransporte }} {{ $m->codigoempresa }}</span></td>

                            <td data-label="Puntos de Control / Evento">
                                {{-- ✅ Limitar altura y hacer scroll interno si hay muchos puntos --}}
                                <div class="rndc-puntos-wrap">
                                    @forelse ($m->puntosControl as $pc)
                                        <div class="rndc-punto border rounded-3 p-2 mb-2">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div>
                                                    <div class="fw-semibold">
                                                        Punto {{ $pc->codpuntocontrol }}
                                                        <span class="text-muted fw-normal">({{ $pc->codmunicipio }})</span>
                                                    </div>
                                                    <div class="small text-muted">
                                                        {{ $pc->direccion }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        {{ $pc->fechacita?->format('d/m/Y') }} {{ $pc->horacita }}
                                                        <span class="mx-1">•</span>
                                                        {{ $pc->tiempopactado }} min
                                                    </div>
                                                    <div class="small text-muted">
                                                        Lat: {{ $pc->latitud }} — Long: {{ $pc->longitud }}
                                                    </div>
                                                </div>

                                                <a href="{{ route('rndc.puntos.evento.create', [$m, $pc]) }}"
                                                   class="btn btn-sm btn-primary text-nowrap">
                                                    Registrar
                                                </a>
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-muted">Sin puntos registrados</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No hay manifiestos registrados.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $manifiestos->links() }}
    </div>
</div>

{{-- ✅ Estilos solo para esta vista --}}
@push('styles')
<style>
  .rndc-sticky th{
    position: sticky;
    top: 0;
    z-index: 2;
  }
  .rndc-table-responsive{
    max-height: calc(100vh - 280px); /* ajusta si quieres */
    overflow: auto;
  }
  .rndc-puntos-wrap{
    max-height: 220px; /* evita filas gigantes */
    overflow: auto;
    padding-right: .25rem;
  }
  .rndc-punto{
    background: #fff;
  }
</style>
@endpush

@endsection
