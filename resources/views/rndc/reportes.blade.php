@extends('layouts.app')

@section('title', 'Reportes RNDC')

@section('content')
<div class="container mt-4">
    <h1 class="h3 mb-3">Reportes RNDC</h1>

    <table class="table table-sm table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha envío</th>
                <th>Tipo operación</th>
                <th>Respuesta</th>
            </tr>
        </thead>
        <tbody>
        @forelse($envios as $envio)
            <tr>
                <td>{{ $envio->id }}</td>
                <td>{{ $envio->created_at }}</td>
                <td>{{ $envio->operation_type }}</td>
                <td>{{ $envio->codigo_respuesta }} - {{ $envio->mensaje_respuesta }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">Aún no hay envíos registrados.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    {{-- @if(method_exists($envios, 'links'))
        {{ $envios->links() }}
    @endif --}}
</div>
@endsection
