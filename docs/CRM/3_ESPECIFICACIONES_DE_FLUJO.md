# Especificaciones de Flujo de Trabajo

## 1. Captura de Lead
Comentario en IG -> Webhook -> IA Clasifica (Sales Lead) -> SMM Inbox (Pestaña Dinero).

## 2. Conversión
SMM hace clic en "Derivar a WA" -> Laravel genera Token -> Paciente envía WA -> System vincula IG + WA + Ficha Clínica.

## 3. Alerta de Crisis
Comentario Negativo -> IA detecta Riesgo Crítico -> Laravel dispara WhatsApp al Director -> SMM Inbox bloquea respuesta automática.

## 4. Cierre de ROI
Paciente paga tratamiento -> El sistema busca el origen -> Atribuye el ingreso al Post original de Instagram.