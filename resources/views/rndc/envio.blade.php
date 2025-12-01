@extends('layouts.app')

@section('title', 'Envió RNDC')

@section('content')
<div class="container mt-4">
    <h1 class="h3 mb-3">Envío Manifiesto RNDC</h1>

    @if(session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('rndc.enviar') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="operationType" class="form-label">Tipo Operación</label>
            <select name="operationType" id="operationType" class="form-select" required>
                <option value="">-- Seleccione --</option>
                <option value="NACIONAL">NACIONAL</option>
                <option value="URBANO">URBANO</option>
                <!-- etc -->
            </select>
            @error('operationType')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="departureTime" class="form-label">Fecha/Hora salida</label>
            <input type="text"
                   name="departureTime"
                   id="departureTime"
                   class="form-control"
                   value="{{ old('departureTime', now()->format('Y-m-d H:i:s')) }}"
                   required>
            <small class="text-muted">Formato: YYYY-MM-DD HH:MM:SS</small>
            @error('departureTime')
                <div class="text-danger small">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="observation" class="form-label">Observación</label>
            <textarea name="observation" id="observation" class="form-control" rows="3">{{ old('observation') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">
            Enviar a RNDC
        </button>
    </form>
</div>
@endsection
