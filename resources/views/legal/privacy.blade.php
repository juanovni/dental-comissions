<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad — OdonCRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 antialiased">
    <div class="max-w-3xl mx-auto px-4 py-12 text-gray-800">
        <h1 class="text-3xl font-bold mb-8">Política de Privacidad</h1>
        <p class="text-sm text-gray-500 mb-8">Última actualización: 1 de julio de 2026</p>

        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-3">1. Información que recopilamos</h2>
            <p class="mb-3">Para operar el sistema de gestión OdonCRM, recopilamos los siguientes datos cuando autorizas la integración con Google Calendar:</p>
            <ul class="list-disc pl-6 space-y-1">
                <li><strong>Dirección de correo electrónico</strong> — para identificar la cuenta de Google que autorizó la integración.</li>
                <li><strong>Tokens de acceso OAuth</strong> — almacenados de forma encriptada para consultar tu calendario.</li>
                <li><strong>Eventos del calendario</strong> — solo lectura de títulos, horas y fechas para verificar disponibilidad de horarios.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-3">2. Finalidad del tratamiento</h2>
            <p class="mb-3">Los datos se utilizan exclusivamente para:</p>
            <ul class="list-disc pl-6 space-y-1">
                <li>Consultar la disponibilidad de horarios de los doctores en la clínica.</li>
                <li>Evitar agendamientos conflictivos al crear citas con pacientes.</li>
                <li>Mostrar al administrador los próximos horarios libres de cada doctor.</li>
            </ul>
        </section>

        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-3">3. Almacenamiento y seguridad</h2>
            <p class="mb-3">Los tokens de acceso se almacenan en nuestra base de datos utilizando cifrado AES-256-CBC. Solo personal autorizado de la clínica tiene acceso a esta información. No compartimos, vendemos ni transferimos tus datos a terceros.</p>
        </section>

        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-3">4. Control del usuario</h2>
            <p class="mb-3">Puedes revocar el acceso de OdonCRM a tu Google Calendar en cualquier momento desde:</p>
            <ul class="list-disc pl-6 space-y-1">
                <li>El panel de administración de OdonCRM, sección Integraciones &rarr; Google Calendar.</li>
                <li>Tu cuenta de Google: <a href="https://myaccount.google.com/permissions" class="text-blue-600 underline" target="_blank">myaccount.google.com/permissions</a>.</li>
            </ul>
            <p class="mt-3">Al revocar el acceso, los tokens almacenados se eliminan inmediatamente.</p>
        </section>

        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-3">5. Retención de datos</h2>
            <p class="mb-3">Conservamos los datos mientras el doctor mantenga activa la integración. Una vez que se desconecta la cuenta, los tokens se eliminan. Los registros de actividad pueden conservarse por motivos operativos hasta 12 meses.</p>
        </section>

        <section class="mb-8">
            <h2 class="text-xl font-semibold mb-3">6. Contacto</h2>
            <p>Para cualquier consulta sobre esta política de privacidad, puedes contactarnos a través del panel de administración de OdonCRM.</p>
        </section>
    </div>
</body>
</html>
