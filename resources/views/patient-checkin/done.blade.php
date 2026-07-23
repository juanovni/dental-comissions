<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Llegada registrada</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">
            Llegada registrada
        </h1>
        <p class="text-gray-500 mb-2">
            Gracias, tu llegada ha sido confirmada.
        </p>
        @if ($doctorName)
            <p class="text-gray-500">
                El Dr(a). {{ $doctorName }} te atendera en breve.
            </p>
        @endif

        <p class="mt-8 text-xs text-gray-400">
            OdonCRM &mdash; Control de pacientes
        </p>
    </div>
</body>
</html>
