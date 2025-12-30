# Análisis Completo de Base de Datos: wsoqajmy_salvaceroCrm

**Fecha de Análisis:** 24 de diciembre de 2025  
**Base de Datos:** wsoqajmy_salvaceroCrm  
**Total de Tablas:** 74 (73 tablas base + 1 vista)

---

## 📊 RESUMEN EJECUTIVO

### Estadísticas Principales
| Tabla | Registros |
|-------|-----------|
| cli_cliente | 1 |
| op_oportunidad | 2,870 |
| vent_cliente | 2,156 |
| cli_cliente_call | 33,083 |
| op_oportunidad_fase_log | 187 |
| adm_usuario | 60 |
| adm_Empresas | 2 |

---

## 🏗️ MÓDULOS PRINCIPALES

### 1. **ADMINISTRACIÓN (adm_*)**
Gestión de empresas, usuarios, roles, permisos y configuración del sistema.

#### Tablas Clave:
- **adm_Empresas** - Información de empresas cliente (2 registros)
  - tenant_uid, razon_social, plan_id, estado, fecha_expira
  
- **adm_usuario** (60 usuarios) - Usuarios del sistema
  - rol_id → adm_rol
  - sucursal_id → adm_sucursales
  - Estados: activo/inactivo
  
- **adm_rol** - Roles disponibles en el sistema
  
- **adm_Usuarios_Admin** - Administradores del sistema
  - Diferentes estructura que adm_usuario
  - email (UNIQUE)
  
- **adm_menu** - Menú del sistema
  - id_menu_padre (jerarquía)
  - is_validate_permission

#### Relaciones:
```
adm_usuario → adm_rol (rol_id)
adm_menu_x_usuario → adm_usuario (id_usuario)
adm_menu_x_usuario → adm_menu (id_menu)
cat_permission_det → adm_rol (id_rol)
```

---

### 2. **CLIENTES (cli_*)**
Gestión de clientes prospección y cobranza.

#### Tablas Clave:
- **cli_cliente** (1 registro) - Cliente principal
  - numero_identificacion (clave única)
  - id_asesor_asignado
  - is_contactado, consulta_equifax, correo_enviado
  - fecha_convertido_cobranza
  - Campos de auditoría: date_create, user_create, date_update

- **cli_cliente_call** (33,083 llamadas) 📞
  - Llamadas realizadas a clientes
  - id_call_type, id_call_medio, id_call_response
  - phone, date, minute, description
  - recordatorio, alert_recordatorio
  - estado_llamada, id_call_response_cobranzas
  
- **cli_clientes_asesor_historial** - Historial de cambios de asesor
  - asesor_anterior → asesor_nuevo
  - tipo: asignación, transferencia, etc.
  
- **cli_cliente_form_general** - Formularios dinámicos para clientes
  - id_cliente → cli_cliente
  - id_cli_form_cliente → cli_form_general
  - valor, key_type_input
  
- **cli_form_general** - Definición de formularios
  - id_input_general → cat_inputs_general
  - orden, view_table, obligatorio
  - view_table_cobranzas (específico para cobranza)

- **cli_equifax** - Datos de consultas a Equifax
  - cedula, score, score_sobreendeudamiento
  - Información demográfica y crediticia completa
  - id_oportunidad (relación con oportunidades)
  
- **cli_op_calender** - Calendario de eventos cliente-oportunidad
  - fecha, time, nombre, descripcion
  - id_asistencia, id_motivo
  
- **cli_op_calender_cobranzas** - Calendario específico de cobranzas
  - Misma estructura que cli_op_calender

- **cli_wolkbox_llamadas_log** - Log de integraciones Wolkbox
  - agente_id, cliente_id, telefono_cliente
  - success, response_json

#### Relaciones Críticas:
```
cli_cliente_form_general.id_cliente → cli_cliente
cli_cliente_form_general.id_cli_form_cliente → cli_form_general
cli_cliente_call.id_cliente → cli_cliente
cli_form_general_det → cli_form_general
cli_equifax.id_oportunidad → op_oportunidad
```

---

### 3. **OPORTUNIDADES (op_*)**
Gestión de oportunidades de venta y pipeline de ventas.

#### Tablas Clave:
- **op_oportunidad** (2,870 oportunidades) 🎯
  - numero_identificacion (cliente)
  - id_asesor, id_cliente
  - nombre, estado, id_fase
  - importe, importe_esperado
  - fecha_cierre (proyectada)
  - modulo (tipo de producto)
  - opciones_logistica_id
  - Auditoría: date_create, user_create, date_update, user_update

- **op_oportunidad_fase_log** (187 cambios) - Historial de cambios de fase
  - id_oportunidad → op_oportunidad
  - id_fase_anterior → id_fase_nueva (cat_fase)
  - fecha_cambio, id_usuario
  - **KPI IMPORTANTE:** Permite medir tiempo en cada fase
  
- **op_oportunidad_asesor_historial** - Cambios de asesor asignado
  - asesor_anterior → asesor_nuevo
  - usuario, tipo, fecha
  
- **op_oportunidad_importe_log** - Historial de cambios en importes
  - campo_modificado (ENUM: importe, importe_esperado)
  - valor_anterior, valor_nuevo
  - diferencia, porcentaje_cambio
  - fecha_cambio, id_usuario, id_asesor
  - **KPI IMPORTANTE:** Auditoría completa de cambios de montos
  
- **op_oportunidad_form_general** - Datos dinámicos de oportunidad
  - id_oportunidad → op_oportunidad
  - id_op_form_oportunidad → op_form_general
  - valor, key_type_input
  
- **op_form_general** - Definición de formularios para oportunidades
  - Similar a cli_form_general
  
- **op_cierre_visitas** - Registro de cierre de visitas
  - cupo_anterior, cupo_nuevo
  - fase_anterior, fase_nueva
  - motivo_cierre, id_calidad_cliente
  - sucursal_cierre
  
- **op_cierre_visitas_atencion** - Tipo de atención en cierre
  - tipo_atencion (ENUM)
  
- **op_cierre_visitas_motivos** - Catálogo de motivos de cierre
  - id_fase (vinculado a fase)
  
- **op_logistica_opciones** - Opciones de logística
  
- **op_logistica_log_opciones** - Log de cambios en logística
  
- **op_oportunidad_registro_verificacion** - Auditoría de cambios
  - descripcion, empresa_id

#### Relaciones Críticas:
```
op_oportunidad.id_fase → cat_fase
op_oportunidad.id_asesor → adm_usuario
op_oportunidad_fase_log.id_oportunidad → op_oportunidad
op_oportunidad_importe_log.id_oportunidad → op_oportunidad
op_oportunidad_form_general.id_oportunidad → op_oportunidad
cli_equifax.id_oportunidad → op_oportunidad
```

---

### 4. **VENTAS (vent_*)**
Gestión de clientes y facturas de ventas.

#### Tablas Clave:
- **vent_cliente** (2,156 clientes) 
  - numero_identificacion
  - id_asesor_asignado
  - estado (activo/inactivo)
  - user_create (vinculación con usuario)
  - Similar a cli_cliente pero separado
  
- **vent_cliente_call** - Llamadas de ventas
  - Estructura idéntica a cli_cliente_call
  
- **vent_cliente_form_general** - Formularios de clientes ventas
  
- **vent_form_general** - Definición de formularios
  
- **vent_facturas_saga_cliente** - Integración con sistema Saga
  - id_docventa, tx_tipodoc
  - tx_cliente, vm_total
  - bd_estado, id_vendedor
  - **IMPORTANTE:** Conexión con sistema externo Saga

#### Relaciones:
```
vent_cliente → adm_usuario (id_asesor_asignado)
vent_cliente_form_general.id_cliente → vent_cliente
vent_cliente_form_general.id_cli_form_cliente → vent_form_general
```

---

### 5. **CATÁLOGOS (cat_*)**
Datos maestros y configuración del sistema.

#### Tablas de Referencia:
- **cat_fase** - Fases del pipeline
  - nombre (Prospecto, Calificado, Propuesta, etc.)
  - opciones_cierre_visita (bit)
  - obligatorio_cupo_cierre (bit)
  
- **cat_call_type** - Tipos de llamadas (Inbound, Outbound)
  
- **cat_call_medio** - Medio de contacto (Teléfono, Email, etc.)
  
- **cat_call_motivo** - Motivos de llamada (Seguimiento, Cierre, etc.)
  
- **cat_call_motivo_cobranzas** - Motivos específicos de cobranza
  
- **cat_call_response** - Respuestas de llamada (Contactado, No disponible, etc.)
  - semaforo (ENUM para control visual)
  
- **cat_call_response_cobranzas** - Respuestas de cobranza
  
- **cat_calidad_cliente** - Clasificación de calidad de cliente
  - operacion, activo
  
- **cat_asistencia** - Tipos de asistencia
  
- **cat_inputs_general** - Tipos de inputs HTML (texto, select, checkbox, etc.)
  - type_html, code_html
  
- **cat_inputs_general_det** - Opciones de inputs
  - Relacionado a cat_inputs_general
  
- **cat_permission** - Permisos del sistema
  - code, status
  
- **cat_permission_det** - Asignación de permisos a roles
  - id_rol → adm_rol
  - id_permission → cat_permission
  - status_permission
  - id_menu → adm_menu
  
- **cat_relacion** - Relaciones entre formularios cliente y oportunidad
  - id_cli_form_cliente → cli_form_general
  - id_op_form_oportunidad → op_form_general
  - estado (activa/inactiva)

---

### 6. **NOVEDADES/GAMIFICACIÓN (novedades_*)**
Sistema de noticias, tests, logros y notificaciones.

#### Tablas Clave:
- **novedades_noticias** - Noticias corporativas
  - categoria (ENUM)
  - prioridad, tipo
  - Métricas: vistas_totales, likes_totales, compartidas_totales
  - comentarios_habilitados, notificacion_push, notificacion_email
  - roles_objetivo (JSON)
  
- **novedades_tests** - Tests de capacitación
  - categoria, dificultad
  - puntaje_minimo_aprobacion
  - intentos_permitidos, tiempo_limite_minutos
  - Métricas: participantes_totales, aprobados_totales, promedio_puntaje
  
- **novedades_test_preguntas** - Preguntas de tests
  - pregunta_id → novedades_tests
  - tipo (ENUM), puntos, orden
  
- **novedades_test_opciones** - Opciones de respuesta
  - pregunta_id → novedades_test_preguntas
  - es_correcta, puntos_parciales, orden
  
- **novedades_test_resultados** - Resultados de tests por usuario
  - test_id → novedades_tests
  - usuario_id
  - puntaje_obtenido, porcentaje_aprobacion, aprobado
  - tiempo_total_segundos
  - preguntas_correctas/incorrectas/sin_responder
  - respuestas_json, certificado_generado
  
- **novedades_logros** - Logros y objetivos
  - categoria (ENUM)
  - tipo_condicion (ENUM)
  - valor_objetivo, puntos_otorgados
  - condiciones_json
  
- **novedades_usuario_logros** - Logros obtenidos por usuario
  - usuario_id → novedades_usuario_logros
  - logro_id → novedades_logros
  - progreso_actual, completado
  
- **novedades_videos** - Videos de capacitación
  - categoria, archivo_url, thumbnail_url
  - duracion_segundos, tamaño_archivo_mb
  - Métricas: vistas_totales, likes_totales
  
- **novedades_comentarios** - Comentarios en contenido
  - tipo_contenido (ENUM), contenido_id
  - usuario_id, comentario_padre_id (jerarquía)
  - activo, moderado, likes, dislikes, reportado
  
- **novedades_notificaciones** - Notificaciones del sistema
  - usuario_id → adm_usuario
  - tipo_contenido, contenido_id
  - leida, enviada_push, enviada_email
  - programada_para
  
- **novedades_progreso_usuario** - Progreso en contenido
  - usuario_id, tipo_contenido, contenido_id
  - visto, completado, favorito
  - fecha_primera_vista, fecha_ultima_vista, fecha_completado
  - progreso_porcentaje, tiempo_total_segundos, numero_visitas
  - valoracion, compartido

---

## 📈 ANÁLISIS DE RELACIONES

### Relaciones 1-a-Muchos Principales:
```
adm_Empresas → adm_Empresas_Planes, adm_usuario, adm_Empresa_Contactos
adm_usuario → adm_menu_x_usuario, cli_cliente, op_oportunidad, adm_login_auditoria
adm_rol → adm_usuario, cat_permission_det
cat_fase → op_oportunidad, op_cierre_visitas, op_cierre_visitas_motivos
cli_cliente → cli_cliente_call, cli_cliente_form_general, cli_op_calender
op_oportunidad → op_oportunidad_fase_log, op_oportunidad_asesor_historial, op_oportunidad_importe_log
vent_cliente → vent_cliente_call, vent_cliente_form_general
```

### Relaciones Many-to-Many (a través de tablas puente):
```
adm_usuario ←→ adm_menu (vía adm_menu_x_usuario)
cli_form_general ←→ op_form_general (vía cat_relacion)
cat_permission ←→ adm_rol (vía cat_permission_det)
```

### Jerarquías:
```
adm_menu (id_menu_padre)
novedades_comentarios (comentario_padre_id)
cat_inputs_general → cat_inputs_general_det
```

---

## 🎯 DIMENSIONES PARA KPIs

### 1. **VENTAS & OPORTUNIDADES**
- Total oportunidades: 2,870
- Oportunidades por fase
- Valor total en pipeline
- Tasa de conversión por fase
- Tiempo promedio en cada fase (desde op_oportunidad_fase_log)
- Cambios de importe (auditoría en op_oportunidad_importe_log)

### 2. **GESTIÓN DE CLIENTES**
- Total clientes: 2,156 (vent_cliente) + 1 (cli_cliente)
- Clientes contactados vs no contactados (is_contactado)
- Clientes asignados por asesor
- Clientes convertidos a cobranza (fecha_convertido_cobranza)
- Consultas Equifax realizadas

### 3. **ACTIVIDAD DE VENTAS**
- Total llamadas: 33,083 (cli_cliente_call)
- Llamadas por tipo/medio/respuesta
- Tasa de contactabilidad
- Llamadas por asesor
- Efectividad de llamadas (respuestas positivas)

### 4. **GESTIÓN DE ASESORES**
- Usuarios activos: 60
- Oportunidades por asesor
- Valor en cartera por asesor
- Historiales de cambios de asesor
- Eficiencia por asesor

### 5. **CALIDAD & COMPLIANCE**
- Auditoría de cambios (op_oportunidad_registro_verificacion)
- Cambios no justificados (op_oportunidad_importe_log)
- Calidad de cliente (cat_calidad_cliente)
- Log de cambios de fase

### 6. **GAMIFICACIÓN & ENGAGEMENT**
- Tests completados vs totales
- Tasa de aprobación de tests
- Logros ganados por usuario
- Videos vistos y completados
- Participación en comentarios

---

## 📋 VISTA (VIEW)

### v_reporte_cambios_importes
```
SELECT
  id_log, id_oportunidad, nombre_oportunidad, asesor_actual,
  campo_modificado, valor_anterior, valor_nuevo, diferencia, 
  porcentaje_cambio, fecha_cambio, usuario_modifico, 
  observaciones, tipo_cambio, nivel_impacto
FROM op_oportunidad_importe_log
```
**Propósito:** Auditoría de cambios en importes con contexto de oportunidad

---

## 🔍 CAMPOS CLAVE PARA ANÁLISIS

### Timestamps Críticos:
- date_create, user_create (creación)
- date_update, user_update (última modificación)
- fecha_cambio, fecha_cierre, fecha_expira
- fecha_convertido_cobranza

### Estados (ENUM):
- op_oportunidad.estado
- cli_cliente.estado
- vent_cliente.estado
- adm_usuario.estado
- Múltiples cat_* estados

### Auditoría:
- Todas las tablas principales tienen user_create/date_create
- Muchas tienen logs dedicados (*_log tables)
- op_oportunidad_registro_verificacion para cambios sensibles

---

## 💡 RECOMENDACIONES PARA KPIs

### Tablas Principales para Consultas:
1. **op_oportunidad** - Centro de análisis de ventas
2. **op_oportunidad_fase_log** - Flujo y velocidad
3. **op_oportunidad_importe_log** - Control financiero
4. **cli_cliente_call** / **vent_cliente_call** - Actividad
5. **op_cierre_visitas** - Cierre efectivo

### Índices Recomendados (si no existen):
```sql
CREATE INDEX idx_op_oportunidad_asesor ON op_oportunidad(id_asesor);
CREATE INDEX idx_op_oportunidad_fase ON op_oportunidad(id_fase, estado);
CREATE INDEX idx_op_oportunidad_fecha ON op_oportunidad(date_create);
CREATE INDEX idx_cli_cliente_call_asesor ON cli_cliente_call(user_create);
CREATE INDEX idx_op_fase_log_fecha ON op_oportunidad_fase_log(fecha_cambio);
```

### Tablas de Hechos para Data Warehouse:
- **Hechos de Oportunidades:** op_oportunidad (con historia en *_log)
- **Hechos de Actividad:** cli_cliente_call, vent_cliente_call
- **Hechos de Cambios:** op_oportunidad_importe_log, op_oportunidad_fase_log
- **Dimensiones:** adm_usuario, cat_fase, cat_call_response, vent_cliente, cli_cliente

---

## 🔗 CONEXIONES CRUZADAS IMPORTANTES

```
cli_cliente ←→ op_oportunidad (numero_identificacion no es clave ajena, pero vincula)
cli_cliente ←→ vent_cliente (número_identificacion, pero son módulos separados)
cli_equifax → op_oportunidad (id_oportunidad)
adm_usuario ← op_oportunidad (id_asesor)
adm_usuario ← vent_cliente (id_asesor_asignado)
adm_usuario ← cli_cliente (id_asesor_asignado)
```

---

## 📊 TABLA DE VOLUMEN ESPERADO

| Entidad | Volumen Actual | Tipo |
|---------|----------------|------|
| Empresas | 2 | Pequeño |
| Usuarios | 60 | Pequeño |
| Clientes | 2,157 | Mediano |
| Oportunidades | 2,870 | Mediano |
| Llamadas | 33,083 | Grande |
| Logs de Cambios | 187+ | Creciente |

---

**Este análisis está completo y SIN MODIFICACIONES a la base de datos. Solo lectura.**
