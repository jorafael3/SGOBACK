# Diagrama de Relaciones - Base de Datos wsoqajmy_salvaceroCrm

## 📊 MAPA DE ENTIDADES

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        ADMINISTRACIÓN (adm_*)                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  adm_Empresas ────┬─→ adm_Empresas_Planes                               │
│       │           ├─→ adm_Empresa_Contactos                             │
│       │           └─→ adm_Empresa_Parametros                            │
│       │                                                                   │
│  adm_usuario ─────┬─→ adm_rol (1:N)                                     │
│       │           ├─→ adm_menu_x_usuario                                │
│       │           ├─→ adm_login_auditoria                               │
│       │           ├─→ adm_login_two_factor                              │
│       │           └─→ adm_sucursales                                    │
│       │                                                                   │
│  adm_Usuarios_Admin ──→ adm_Usuarios_Admin_Roles                        │
│       │                                                                   │
│  adm_menu ────────┬─→ adm_menu (self-join: id_menu_padre)               │
│                   └─→ adm_menu_x_usuario                                │
│                                                                           │
│  adm_rol ────────→ cat_permission_det                                   │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                     CATALOGOS (cat_*)                                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  cat_fase ────────┬─→ op_oportunidad                                    │
│                   ├─→ op_cierre_visitas                                 │
│                   └─→ op_cierre_visitas_motivos                         │
│                                                                           │
│  cat_call_type ─→ cli_cliente_call                                      │
│  cat_call_medio ─→ cli_cliente_call                                     │
│  cat_call_response ─→ cli_cliente_call                                  │
│  cat_call_motivo ─→ cli_cliente_call                                    │
│                                                                           │
│  cat_call_response_cobranzas ─→ cli_cliente_call                        │
│  cat_call_motivo_cobranzas ─→ cli_op_calender_cobranzas                │
│                                                                           │
│  cat_calidad_cliente ─→ op_cierre_visitas                               │
│                                                                           │
│  cat_asistencia ──→ cli_op_calender                                     │
│                  └→ cli_op_calender_cobranzas                           │
│                                                                           │
│  cat_inputs_general ──┬─→ cat_inputs_general_det                        │
│                       ├─→ cli_form_general                              │
│                       ├─→ op_form_general                               │
│                       ├─→ vent_form_general                             │
│                       └─→ cat_relacion (indirectamente)                 │
│                                                                           │
│  cat_permission ──→ cat_permission_det                                  │
│                                                                           │
│  cat_relacion ────┬─→ cli_form_general (id_cli_form_cliente)            │
│                   └─→ op_form_general (id_op_form_oportunidad)          │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                  CLIENTES - PROSPECCIÓN (cli_*)                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  cli_cliente ──────┬─→ cli_cliente_call (33,083 llamadas) 📞            │
│       │            ├─→ cli_cliente_form_general                         │
│       │            ├─→ cli_clientes_asesor_historial                    │
│       │            ├─→ cli_op_calender                                  │
│       │            ├─→ cli_equifax                                      │
│       │            └─→ cli_wolkbox_llamadas_log                         │
│       │                                                                   │
│  cli_form_general ──┬─→ cli_cliente_form_general                        │
│       │             ├─→ cli_form_general_det                            │
│       │             ├─→ cli_form_general_option                         │
│       │             └─→ cat_relacion                                    │
│       │                                                                   │
│  cli_form_general_det ──→ cli_form_general                              │
│                                                                           │
│  cli_equifax ─────→ op_oportunidad                                      │
│                                                                           │
│  cli_op_calender ──→ cat_asistencia                                     │
│                  └→ cat_call_motivo                                     │
│                                                                           │
│  cli_op_calender_cobranzas ──→ cat_asistencia                           │
│                             └→ cat_call_motivo_cobranzas                │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                  OPORTUNIDADES - VENTAS (op_*)                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  op_oportunidad ────┬─→ op_oportunidad_fase_log (187 cambios)           │
│  (2,870 registros) │  ├─→ op_oportunidad_asesor_historial               │
│       │             │  ├─→ op_oportunidad_importe_log (auditoría)       │
│       │             │  ├─→ op_oportunidad_form_general                  │
│       │             │  ├─→ op_oportunidad_registro_verificacion         │
│       │             │  ├─→ op_cierre_visitas                            │
│       │             │  ├─→ op_cierre_visitas_atencion                   │
│       │             │  └─→ cli_equifax                                  │
│       │             │                                                     │
│       └──────────→ cat_fase                                             │
│       └──────────→ adm_usuario (id_asesor)                              │
│       └──────────→ op_logistica_opciones                                │
│                                                                           │
│  op_form_general ──┬─→ op_oportunidad_form_general                      │
│       │            ├─→ op_form_general_det                              │
│       │            ├─→ op_form_general_option                           │
│       │            └─→ cat_relacion                                     │
│       │                                                                   │
│  op_form_general_det ──→ op_form_general                                │
│                                                                           │
│  op_logistica_opciones ──┬─→ op_oportunidad                             │
│                          └─→ op_logistica_log_opciones                  │
│                                                                           │
│  op_cierre_visitas ──→ cat_fase                                         │
│                    └─→ op_cierre_visitas_motivos                        │
│                                                                           │
│  op_cierre_visitas_motivos ──→ cat_fase                                 │
│                                                                           │
│  op_cierre_visitas_atencion ──→ adm_usuario                             │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                   VENTAS - COMERCIAL (vent_*)                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  vent_cliente ──────┬─→ vent_cliente_call                               │
│  (2,156 registros) │  ├─→ vent_cliente_form_general                     │
│       │             │  └─→ vent_facturas_saga_cliente (SAGA)            │
│       │             │                                                     │
│       └──→ adm_usuario (id_asesor_asignado)                             │
│                                                                           │
│  vent_form_general ──┬─→ vent_cliente_form_general                      │
│       │              ├─→ vent_form_general_det                          │
│       │              ├─→ vent_form_general_option                       │
│       │              └─→ cat_inputs_general                             │
│       │                                                                   │
│  vent_form_general_det ──→ vent_form_general                            │
│                                                                           │
│  vent_facturas_saga_cliente ──→ vent_cliente                            │
│                              └→ adm_usuario (id_vendedor)               │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│               GAMIFICACIÓN - NOVEDADES (novedades_*)                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  novedades_noticias ─────┬─→ novedades_comentarios                      │
│                          └─→ novedades_progreso_usuario                 │
│                                                                           │
│  novedades_tests ────┬─→ novedades_test_preguntas                       │
│                      └─→ novedades_test_resultados                      │
│                                                                           │
│  novedades_test_preguntas ──→ novedades_test_opciones                   │
│                                                                           │
│  novedades_test_resultados ──→ adm_usuario                              │
│                                                                           │
│  novedades_logros ────┬─→ novedades_usuario_logros                      │
│                       └─→ novedades_progreso_usuario                    │
│                                                                           │
│  novedades_usuario_logros ──→ adm_usuario                               │
│                                                                           │
│  novedades_videos ────┬─→ novedades_comentarios                         │
│                       └─→ novedades_progreso_usuario                    │
│                                                                           │
│  novedades_comentarios ──→ novedades_comentarios (self-join: padre)     │
│                        └─→ adm_usuario (usuario_id)                     │
│                                                                           │
│  novedades_notificaciones ──→ adm_usuario                               │
│                                                                           │
│  novedades_progreso_usuario ──→ adm_usuario                             │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                         VISTA (VIEW)                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│  v_reporte_cambios_importes ───→ op_oportunidad_importe_log             │
│     (Auditoría de cambios en importes con contexto)                     │
│                                                                           │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 🔗 FLUJO DE DATOS PRINCIPAL

```
                         ┌─────────────────┐
                         │  adm_Empresas   │
                         │      (2)        │
                         └────────┬────────┘
                                  │
                    ┌─────────────┼─────────────┐
                    │             │             │
            ┌───────▼────────┐    │    ┌────────▼─────────┐
            │  adm_usuario   │    │    │ adm_Usuarios_Admin│
            │     (60)       │    │    │                    │
            └───────┬────────┘    │    └────────────────────┘
                    │             │
        ┌───────────┼─────────────┼──────────┐
        │           │             │          │
    ┌───▼──┐    ┌──▼───────────┐ │   ┌─────▼──────┐
    │adm_  │    │cli_cliente   │ │   │ op_oportunidad│
    │rol   │    │    (1)       │ │   │   (2,870)     │
    │      │    └──┬───────────┘ │   │               │
    └──────┘       │             │   └────────┬──────┘
                   │             │            │
          ┌────────▼─────────┐   │   ┌────────▼──────────────┐
          │ cli_cliente_call │   │   │ op_oportunidad_*_log   │
          │  (33,083) 📞     │   │   │ fase/importe/asesor    │
          └──────────────────┘   │   └────────────────────────┘
                                 │
                         ┌───────▼─────────┐
                         │ vent_cliente    │
                         │   (2,156)       │
                         └─────────────────┘
```

---

## 🔐 RELACIONES DE INTEGRIDAD REFERENCIAL

### Foreign Keys Definidas:

| Tabla | Columna | Referencia | Descripción |
|-------|---------|-----------|-------------|
| adm_menu_x_usuario | id_usuario | adm_usuario.id_usuario | Asignación de menús a usuarios |
| adm_menu_x_usuario | id_menu | adm_menu.id_menu | Menú disponible |
| adm_usuario | rol_id | adm_rol.id_rol | Rol del usuario |
| cat_inputs_general_det | id_input_general | cat_inputs_general.id_input | Detalle de opciones |
| cat_permission_det | id_permission | cat_permission.id_permission | Permiso asignado |
| cat_permission_det | id_rol | adm_rol.id_rol | Rol con permisos |
| cat_relacion | id_cli_form_cliente | cli_form_general.id_cli_form_cliente | Formulario cliente |
| cat_relacion | id_op_form_oportunidad | op_form_general.id_op_form_oportunidad | Formulario oportunidad |
| cli_cliente_form_general | id_cliente | cli_cliente.id_cliente | Cliente |
| cli_cliente_form_general | id_cli_form_cliente | cli_form_general.id_cli_form_cliente | Formulario |
| cli_form_general_det | id_cli_form_general | cli_form_general.id_cli_form_cliente | Formulario base |
| op_oportunidad | id_fase | cat_fase.id_fase | Fase actual |
| op_oportunidad_fase_log | id_oportunidad | op_oportunidad.id_oportunidad | Oportunidad |
| vent_form_general_det | id_cli_form_general | vent_form_general.id_cli_form_cliente | Formulario |
| novedades_test_preguntas | test_id | novedades_tests.id | Test |
| novedades_test_opciones | pregunta_id | novedades_test_preguntas.id | Pregunta |
| novedades_test_resultados | test_id | novedades_tests.id | Test |
| novedades_usuario_logros | logro_id | novedades_logros.id | Logro |

### Relaciones Sin Definir (pero evidentes):

| Tabla | Columna | Referencia Implícita | Tipo |
|-------|---------|----------------------|------|
| cli_cliente | id_asesor_asignado | adm_usuario.id_usuario | Asignación |
| op_oportunidad | id_asesor | adm_usuario.id_usuario | Asignación |
| op_oportunidad | id_cliente | cli_cliente.id_cliente | Cliente |
| vent_cliente | id_asesor_asignado | adm_usuario.id_usuario | Asignación |
| cli_cliente_call | id_cliente | cli_cliente.id_cliente | Cliente |
| cli_cliente_call | id_call_type | cat_call_type.id_call_type | Tipo |
| cli_cliente_call | id_call_medio | cat_call_medio.id_call_medio | Medio |
| cli_cliente_call | id_call_response | cat_call_response.id_call_response | Respuesta |
| cli_cliente_call | user_create | adm_usuario.usuario | Usuario creador |
| cli_equifax | id_oportunidad | op_oportunidad.id_oportunidad | Oportunidad |
| op_cierre_visitas | id_oportunidad | op_oportunidad.id_oportunidad | Oportunidad |
| op_cierre_visitas | id_calidad_cliente | cat_calidad_cliente.id_calidad_cliente | Calidad |
| op_cierre_visitas_atencion | id_oportunidad | op_oportunidad.id_oportunidad | Oportunidad |
| vent_facturas_saga_cliente | id_cliente | vent_cliente.id_cliente | Cliente |

---

## 🔄 CICLOS DE VIDA DE DATOS

### Ciclo de Prospección (cli_*):
```
cli_cliente (nuevo)
    ↓
cli_cliente_call (llamadas realizadas)
    ↓
cli_cliente_form_general (información completada)
    ↓
cli_equifax (consulta crediticia)
    ↓
cli_cliente.fecha_convertido_cobranza (conversión)
```

### Ciclo de Oportunidad (op_*):
```
op_oportunidad (nueva en fase inicial)
    ↓
op_oportunidad_fase_log (cambios de fase)
    ↓
op_oportunidad_importe_log (ajustes de montos)
    ↓
op_oportunidad_asesor_historial (cambios de asesor)
    ↓
op_cierre_visitas (cierre de visita)
    ↓
op_oportunidad.estado = 'Ganada'/'Perdida' (final)
```

### Ciclo de Transacción (vent_*):
```
vent_cliente (nuevo cliente)
    ↓
vent_cliente_call (seguimiento)
    ↓
vent_cliente_form_general (datos completados)
    ↓
vent_facturas_saga_cliente (facturación desde SAGA)
    ↓
transacción completada
```

---

## 📈 TABLA DE VOLUMEN Y CRECIMIENTO

| Entidad | Volumen | Frecuencia | Crecimiento |
|---------|---------|-----------|-------------|
| adm_Empresas | 2 | Anual | Bajo |
| adm_usuario | 60 | Mensual | Medio |
| cli_cliente | 1 | Bajo | Variable |
| op_oportunidad | 2,870 | Diario | Alto |
| cli_cliente_call | 33,083 | Diario | Alto |
| vent_cliente | 2,156 | Diario | Medio |
| op_oportunidad_fase_log | 187+ | Diario | Creciente |
| op_oportunidad_importe_log | 187+ | Diario | Creciente |
| vent_facturas_saga_cliente | ? | Diario | Creciente |

---

## ⚡ PUNTOS CRÍTICOS PARA PERFORMANCE

### Tablas Calientes (Acceso Frecuente):
1. **cli_cliente_call** (33K registros) - Filtrar por fecha/usuario
2. **op_oportunidad** (2.8K registros) - Filtrar por fase/asesor
3. **novedades_comentarios** - Índice en usuario_id, activo
4. **novedades_progreso_usuario** - Índice en usuario_id, fecha

### Operaciones Frecuentes:
- Búsqueda de oportunidades por asesor y fase
- Historial de cambios de fase/importe
- Llamadas realizadas por usuario/fecha
- Progreso de usuarios en contenido

### Índices Recomendados:
```sql
CREATE INDEX idx_op_oportunidad_asesor_fase 
  ON op_oportunidad(id_asesor, id_fase);

CREATE INDEX idx_cli_cliente_call_fecha_usuario 
  ON cli_cliente_call(date, user_create);

CREATE INDEX idx_op_fase_log_fecha_oportunidad 
  ON op_oportunidad_fase_log(fecha_cambio, id_oportunidad);

CREATE INDEX idx_novedades_progreso_usuario_fecha 
  ON novedades_progreso_usuario(usuario_id, fecha_ultima_vista);
```

---

## 🎯 RECOMENDACIONES ARQUITECTURA

1. **Separación de Módulos:** cli_* vs op_* vs vent_* están bien separados
2. **Auditoría:** Buena cobertura de *_log tables
3. **Formularios:** Sistema genérico de formularios dinámicos (bueno)
4. **Cascada:** Considerar agregar ON DELETE CASCADE en *_log tables
5. **Vistas:** Usar v_reporte_cambios_importes como patrón para otras vistas
6. **Integraciones:** vent_facturas_saga_cliente es punto de integración crítico

