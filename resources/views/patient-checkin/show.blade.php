<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar llegada</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            Bienvenido {{ $patientName }}
        </h1>
        <p class="text-gray-500 mb-8">
            Confirma tu llegada para agilizar tu atencion
        </p>

        <form action="{{ route('patient-checkin.confirm', ['token' => $appointment->id]) }}" method="POST">
            @csrf
            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-xl text-lg transition-all active:scale-95">
                Ya llegue
            </button>
        </form>

        <p class="mt-6 text-xs text-gray-400">
            OdonCRM &mdash; Control de pacientes
        </p>
    </div>
</body>
</html>
