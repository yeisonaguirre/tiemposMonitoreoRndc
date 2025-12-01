@extends('layouts.app')

@section('title', 'Registrar evento punto de control')

@section('content')
<div class="container mt-4">

    <h1 class="h4 mb-3">
        Registrar evento — Manifiesto {{ $manifiesto->nummanifiestocarga }} / Punto {{ $punto->codpuntocontrol }}
    </h1>

    <div class="mb-3">
        <a href="{{ route('rndc.manifiestos.show', $manifiesto) }}" class="btn btn-sm btn-outline-secondary">
            &laquo; Volver al manifiesto
        </a>
    </div>

    <div class="row">
        <div class="col-lg-9 mx-auto">

            {{-- Resumen del punto --}}
            <div class="alert alert-light border mb-3">
                <div class="row">
                    <div class="col-md-6 mb-2 mb-md-0">
                        <strong>Dirección:</strong><br>
                        <span class="text-muted">{{ $punto->direccion }}</span>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <strong>Fecha cita:</strong><br>
                        <span class="text-muted">
                            {{ optional($punto->fechacita)->format('d/m/Y') }} {{ $punto->horacita }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>Lat / Long:</strong><br>
                        <span class="text-muted">
                            {{ $punto->latitud }} / {{ $punto->longitud }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <strong>Datos del evento</strong>
                    <span class="text-muted small d-block">
                        Complete la información de llegada y salida para este punto de control.
                    </span>
                </div>

                <div class="card-body">

                    <form method="POST" action="{{ route('rndc.puntos.evento.store', [$manifiesto, $punto]) }}">
                        @csrf

                        {{-- Coordenadas --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Latitud <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="latitud"
                                       class="form-control @error('latitud') is-invalid @enderror"
                                       value="{{ old('latitud', $punto->latitud) }}"
                                       required>
                                @error('latitud')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Longitud <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="longitud"
                                       class="form-control @error('longitud') is-invalid @enderror"
                                       value="{{ old('longitud', $punto->longitud) }}"
                                       required>
                                @error('longitud')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Llegada --}}
                        <h6 class="mt-2 mb-2">Llegada</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Fecha llegada <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="fechallegada"
                                       class="form-control @error('fechallegada') is-invalid @enderror"
                                       value="{{ old('fechallegada', optional($punto->fechacita)->format('d/m/Y')) }}"
                                       placeholder="dd/mm/aaaa"
                                       required>
                                @error('fechallegada')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Hora llegada <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="horallegada"
                                       class="form-control @error('horallegada') is-invalid @enderror"
                                       value="{{ old('horallegada') }}"
                                       placeholder="HH:MM"
                                       required>
                                @error('horallegada')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Salida --}}
                        <h6 class="mt-3 mb-2">Salida</h6>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Fecha salida <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="fechasalida"
                                       class="form-control @error('fechasalida') is-invalid @enderror"
                                       value="{{ old('fechasalida', optional($punto->fechacita)->format('d/m/Y')) }}"
                                       placeholder="dd/mm/aaaa"
                                       required>
                                @error('fechasalida')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Hora salida <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="horasalida"
                                       class="form-control @error('horasalida') is-invalid @enderror"
                                       value="{{ old('horasalida') }}"
                                       placeholder="HH:MM"
                                       required>
                                @error('horasalida')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Enviar evento a RNDC
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>

</div>
@endsection
