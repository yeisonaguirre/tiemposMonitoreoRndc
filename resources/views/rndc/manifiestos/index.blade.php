@extends('layouts.app')

@section('title', 'Manifiestos RNDC')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h1 class="h3">Man.RNDC</h1>

        <div class="d-flex gap-2">
            {{-- Filtros --}}
            <form method="GET" action="{{ route('rndc.manifiestos.index') }}" class="d-flex gap-2">
                <input type="text"
                    name="ingresoidmanifiesto"
                    class="form-control form-control-sm"
                    placeholder="Ingreso ID"
                    value="{{ request('ingresoidmanifiesto') }}">

                <input type="text"
                    name="nummanifiestocarga"
                    class="form-control form-control-sm"
                    placeholder="Manifiesto"
                    value="{{ request('nummanifiestocarga') }}">

                <input type="text"
                    name="numplaca"
                    class="form-control form-control-sm"
                    placeholder="Placa"
                    value="{{ request('numplaca') }}">

                <input type="text"
                    name="codigoempresa"
                    class="form-control form-control-sm"
                    placeholder="Empresa"
                    value="{{ request('codigoempresa') }}">

                <input type="text"
                    name="fechaexpedicion"
                    class="form-control form-control-sm"
                    placeholder="Fecha Exp. (dd/mm/aaaa)"
                    value="{{ request('fechaexpedicion') }}">

                <button type="submit" class="btn btn-sm btn-secondary btn-fixed" title="Aplicar filtros">
                    Buscar
                </button>

                <a href="{{ route('rndc.manifiestos.index') }}" class="btn btn-sm btn-outline-secondary btn-fixed" title="Limpiar filtros">
                    Limpiar
                </a>
            </form>

            {{-- Botón consultar nuevos --}}
            <form method="POST" action="{{ route('rndc.manifiestos.sync') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary btn-fixed">
                    🔄 Consultar nuevos
                </button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">

            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Ingreso ID</th>
                        <th>Manifiesto</th>
                        <th>Placa</th>
                        <th>Fecha Exp.</th>
                        <th>Empresa</th>
                        <th style="width: 40%">Puntos de Control / Evento</th>
                    </tr>
                </thead>

                <tbody>
                @forelse ($manifiestos as $m)
                    <tr>
                        {{-- Link al show del manifiesto --}}
                        <td>
                            <a href="{{ route('rndc.manifiestos.show', $m) }}">
                                {{ $m->ingresoidmanifiesto }}
                            </a>
                        </td>

                        <td>
                            <a href="{{ route('rndc.manifiestos.show', $m) }}">
                                {{ $m->nummanifiestocarga }}
                            </a>
                        </td>

                        <td>{{ $m->numplaca }}</td>
                        <td>{{ optional($m->fechaexpedicionmanifiesto)->format('d/m/Y') }}</td>
                        <td>{{ $m->codigoempresa }}</td>

                        <td>
                            @forelse ($m->puntosControl as $pc)
                                <div class="mb-2 border-bottom pb-1">
                                    <div>
                                        <strong>Punto {{ $pc->codpuntocontrol }}</strong>
                                        <span class="text-muted">({{ $pc->codmunicipio }})</span>
                                    </div>

                                    <small class="text-muted d-block">
                                        {{ $pc->direccion }}<br>
                                        {{ $pc->fechacita?->format('d/m/Y') }} {{ $pc->horacita }} |
                                        Lat: {{ $pc->latitud }} — Long: {{ $pc->longitud }} |
                                        {{ $pc->tiempopactado }} min
                                    </small>

                                    {{-- Botón para ir al formulario de evento --}}
                                    <a href="{{ route('rndc.puntos.evento.create', [$m, $pc]) }}"
                                       class="btn btn-xs btn-sm btn-primary mt-1">
                                        Registrar evento
                                    </a>
                                </div>
                            @empty
                                <span class="text-muted">Sin puntos registrados</span>
                            @endforelse
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

    {{-- Paginación centrada --}}
    <div class="mt-3 d-flex justify-content-center">
        {{ $manifiestos->links() }}
    </div>

</div>
@endsection
