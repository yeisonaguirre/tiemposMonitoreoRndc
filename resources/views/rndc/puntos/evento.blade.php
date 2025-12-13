@extends('layouts.app')

@section('title', 'Registrar evento punto de control')

@section('content')
<div class="container-fluid mt-4">

    {{-- Header --}}
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
        <div>
            <div class="small text-muted">
                <a class="text-decoration-none" href="{{ route('rndc.manifiestos.index') }}">Manifiestos</a>
                <span class="mx-1">/</span>
                <a class="text-decoration-none" href="{{ route('rndc.manifiestos.show', $manifiesto) }}">{{ $manifiesto->nummanifiestocarga }}</a>
                <span class="mx-1">/</span>
                Evento
            </div>

            <h1 class="h5 m-0">
                Registrar evento — Punto <strong>{{ $punto->codpuntocontrol }}</strong>
            </h1>

            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="badge text-bg-dark">Manifiesto: {{ $manifiesto->nummanifiestocarga }}</span>
                <span class="badge text-bg-secondary">Empresa: {{ $manifiesto->codigoempresa }}</span>
                <span class="badge text-bg-light">Placa: {{ $manifiesto->numplaca }}</span>
            </div>
        </div>

        <a href="{{ route('rndc.manifiestos.show', $manifiesto) }}" class="btn btn-sm btn-outline-secondary">
            &laquo; Volver
        </a>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-4">
            {{-- Resumen del punto --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="fw-semibold mb-2">Resumen del punto</div>

                    <div class="mb-2">
                        <div class="small text-muted">Dirección</div>
                        <div class="fw-semibold">{{ $punto->direccion }}</div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="small text-muted">Fecha cita</div>
                            <div class="fw-semibold">{{ optional($punto->fechacita)->format('d/m/Y') ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Hora cita</div>
                            <div class="fw-semibold">{{ $punto->horacita ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <div class="small text-muted">Latitud</div>
                            <div class="fw-semibold">{{ $punto->latitud }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Longitud</div>
                            <div class="fw-semibold">{{ $punto->longitud }}</div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="small text-muted">
                        Completa llegada/salida y envía a RNDC.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="fw-semibold">Datos del evento</div>
                    <div class="text-muted small">Los campos con <span class="text-danger">*</span> son obligatorios.</div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('rndc.puntos.evento.store', [$manifiesto, $punto]) }}">
                        @csrf

                        {{-- Coordenadas --}}
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Latitud <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="latitud"
                                       inputmode="decimal"
                                       class="form-control @error('latitud') is-invalid @enderror"
                                       value="{{ old('latitud', $punto->latitud) }}"
                                       placeholder="Ej: 4.7123"
                                       required>
                                @error('latitud') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Longitud <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="longitud"
                                       inputmode="decimal"
                                       class="form-control @error('longitud') is-invalid @enderror"
                                       value="{{ old('longitud', $punto->longitud) }}"
                                       placeholder="Ej: -74.0721"
                                       required>
                                @error('longitud') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            {{-- Llegada --}}
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h6 class="m-0">Llegada</h6>
                                    {{-- Opcional: copiar llegada a salida --}}
                                    <div class="form-check form-switch small">
                                        <input class="form-check-input" type="checkbox" id="copiarLlegadaSalida">
                                        <label class="form-check-label text-muted" for="copiarLlegadaSalida">Copiar a salida</label>
                                    </div>
                                </div>
                                <hr class="mt-2 mb-3">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Fecha llegada <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="fechallegada"
                                       class="form-control @error('fechallegada') is-invalid @enderror"
                                       value="{{ old('fechallegada', optional($punto->fechacita)->format('d/m/Y')) }}"
                                       placeholder="dd/mm/aaaa"
                                       required>
                                @error('fechallegada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Hora llegada <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="horallegada"
                                       inputmode="numeric"
                                       class="form-control @error('horallegada') is-invalid @enderror"
                                       value="{{ old('horallegada') }}"
                                       placeholder="HH:MM"
                                       required>
                                @error('horallegada') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Salida --}}
                            <div class="col-12 mt-2">
                                <h6 class="m-0">Salida</h6>
                                <hr class="mt-2 mb-3">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Fecha salida <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="fechasalida"
                                       class="form-control @error('fechasalida') is-invalid @enderror"
                                       value="{{ old('fechasalida', optional($punto->fechacita)->format('d/m/Y')) }}"
                                       placeholder="dd/mm/aaaa"
                                       required>
                                @error('fechasalida') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Hora salida <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="horasalida"
                                       inputmode="numeric"
                                       class="form-control @error('horasalida') is-invalid @enderror"
                                       value="{{ old('horasalida') }}"
                                       placeholder="HH:MM"
                                       required>
                                @error('horasalida') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                Enviar evento a RNDC
                            </button>
                            <a href="{{ route('rndc.manifiestos.show', $manifiesto) }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- JS pequeño (opcional) para “Copiar llegada → salida” --}}
            @push('scripts')
            <script>
              (function () {
                const toggle = document.getElementById('copiarLlegadaSalida');
                if (!toggle) return;

                const fL = document.querySelector('input[name="fechallegada"]');
                const hL = document.querySelector('input[name="horallegada"]');
                const fS = document.querySelector('input[name="fechasalida"]');
                const hS = document.querySelector('input[name="horasalida"]');

                toggle.addEventListener('change', function () {
                  if (this.checked) {
                    if (fL && fS) fS.value = fL.value || fS.value;
                    if (hL && hS) hS.value = hL.value || hS.value;
                  }
                });
              })();
            </script>
            @endpush

        </div>
    </div>

</div>
@endsection
