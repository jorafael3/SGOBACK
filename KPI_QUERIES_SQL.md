# KPIs - Queries SQL Propuestas

Base de datos: `wsoqajmy_salvaceroCrm`

---

## 🎯 KPI #1: VALOR TOTAL EN PIPELINE POR FASE

```sql
SELECT 
  f.nombre as fase,
  COUNT(o.id_oportunidad) as cantidad_oportunidades,
  SUM(o.importe) as valor_total_importe,
  SUM(o.importe_esperado) as valor_esperado,
  AVG(o.importe) as importe_promedio,
  o.estado
FROM op_oportunidad o
LEFT JOIN cat_fase f ON o.id_fase = f.id_fase
GROUP BY o.id_fase, f.nombre, o.estado
ORDER BY valor_total_importe DESC;
```

---

## 🎯 KPI #2: TASA DE CONVERSIÓN POR FASE

```sql
SELECT 
  f.nombre as fase_origen,
  f2.nombre as fase_destino,
  COUNT(*) as cambios,
  AVG(DATEDIFF(DAY, (
    SELECT fecha_cambio FROM op_oportunidad_fase_log opl2 
    WHERE opl2.id_oportunidad = opl.id_oportunidad 
    AND opl2.id_log < opl.id_log 
    ORDER BY opl2.id_log DESC LIMIT 1
  ), opl.fecha_cambio)) as dias_en_fase_promedio
FROM op_oportunidad_fase_log opl
LEFT JOIN cat_fase f ON opl.id_fase_anterior = f.id_fase
LEFT JOIN cat_fase f2 ON opl.id_fase_nueva = f2.id_fase
GROUP BY opl.id_fase_anterior, opl.id_fase_nueva, f.nombre, f2.nombre
ORDER BY cambios DESC;
```

---

## 🎯 KPI #3: DESEMPEÑO POR ASESOR

```sql
SELECT 
  u.nombre,
  u.apellido,
  COUNT(DISTINCT o.id_oportunidad) as total_oportunidades,
  SUM(o.importe) as valor_total,
  AVG(o.importe) as importe_promedio,
  COUNT(DISTINCT CASE WHEN o.estado = 'Ganada' THEN o.id_oportunidad END) as oportunidades_ganadas,
  COUNT(DISTINCT CASE WHEN o.estado = 'Perdida' THEN o.id_oportunidad END) as oportunidades_perdidas,
  ROUND(100.0 * COUNT(DISTINCT CASE WHEN o.estado = 'Ganada' THEN o.id_oportunidad END) / 
        NULLIF(COUNT(DISTINCT o.id_oportunidad), 0), 2) as tasa_ganancia_porcentaje,
  COUNT(DISTINCT cc.id_call) as total_llamadas
FROM adm_usuario u
LEFT JOIN op_oportunidad o ON u.id_usuario = o.id_asesor
LEFT JOIN cli_cliente_call cc ON u.usuario = cc.user_create
WHERE u.estado = 'activo'
GROUP BY u.id_usuario, u.nombre, u.apellido
ORDER BY valor_total DESC;
```

---

## 🎯 KPI #4: ACTIVIDAD DE LLAMADAS

```sql
SELECT 
  DATE(cc.date) as fecha,
  cr.name as respuesta,
  cm.nombre as motivo,
  cct.name as tipo_llamada,
  COUNT(cc.id_call) as total_llamadas,
  ROUND(100.0 * COUNT(*) / SUM(COUNT(*)) OVER (PARTITION BY DATE(cc.date)), 2) as porcentaje_del_dia
FROM cli_cliente_call cc
LEFT JOIN cat_call_response cr ON cc.id_call_response = cr.id_call_response
LEFT JOIN cat_call_motivo cm ON cc.id_call_motivo = cm.id_call_motivo
LEFT JOIN cat_call_type cct ON cc.id_call_type = cct.id_call_type
GROUP BY DATE(cc.date), cr.name, cm.nombre, cct.name
ORDER BY fecha DESC, total_llamadas DESC;
```

---

## 🎯 KPI #5: TASA DE CONTACTABILIDAD

```sql
SELECT 
  DATE(cc.date) as fecha,
  cr.code as codigo_respuesta,
  cr.semaforo as semaforo,
  COUNT(cc.id_call) as total_intentos,
  ROUND(100.0 * COUNT(*) / SUM(COUNT(*)) OVER (PARTITION BY DATE(cc.date)), 2) as porcentaje,
  CASE 
    WHEN cr.semaforo = 'Verde' THEN 'Contactado'
    WHEN cr.semaforo = 'Amarillo' THEN 'Parcial'
    ELSE 'No contactado'
  END as estado_contacto
FROM cli_cliente_call cc
LEFT JOIN cat_call_response cr ON cc.id_call_response = cr.id_call_response
GROUP BY DATE(cc.date), cr.code, cr.semaforo
ORDER BY fecha DESC;
```

---

## 🎯 KPI #6: AUDITORIA DE CAMBIOS EN IMPORTES

```sql
SELECT 
  o.nombre as oportunidad,
  u.nombre as asesor,
  opl.campo_modificado,
  opl.valor_anterior,
  opl.valor_nuevo,
  opl.diferencia,
  opl.porcentaje_cambio,
  opl.fecha_cambio,
  opl.id_usuario as usuario_modifico
FROM op_oportunidad_importe_log opl
LEFT JOIN op_oportunidad o ON opl.id_oportunidad = o.id_oportunidad
LEFT JOIN adm_usuario u ON opl.id_asesor = u.id_usuario
WHERE ABS(opl.porcentaje_cambio) > 10  -- Cambios significativos (>10%)
ORDER BY opl.fecha_cambio DESC;
```

---

## 🎯 KPI #7: CLIENTES CONVERTIDOS A COBRANZA

```sql
SELECT 
  DATE(cc.fecha_convertido_cobranza) as fecha_conversion,
  COUNT(DISTINCT cc.id_cliente) as clientes_convertidos,
  COUNT(DISTINCT cc.id_asesor_asignado) as asesores_involucrados,
  COUNT(DISTINCT CASE WHEN cc.consulta_equifax = 1 THEN cc.id_cliente END) as con_equifax
FROM cli_cliente cc
WHERE cc.fecha_convertido_cobranza IS NOT NULL
GROUP BY DATE(cc.fecha_convertido_cobranza)
ORDER BY fecha_conversion DESC;
```

---

## 🎯 KPI #8: EFICIENCIA DE FORMULARIOS

```sql
SELECT 
  cf.nombre,
  COUNT(DISTINCT ccfg.id_cliente) as clientes_con_datos,
  COUNT(DISTINCT ccfg.id_valor) as total_respuestas,
  ROUND(100.0 * COUNT(DISTINCT ccfg.id_cliente) / 
    NULLIF((SELECT COUNT(DISTINCT id_cliente) FROM cli_cliente), 0), 2) as cobertura_porcentaje
FROM cli_form_general cf
LEFT JOIN cli_cliente_form_general ccfg ON cf.id_cli_form_cliente = ccfg.id_cli_form_cliente
WHERE cf.estado = 'activo' AND cf.obligatorio = 1
GROUP BY cf.id_cli_form_cliente, cf.nombre
ORDER BY clientes_con_datos DESC;
```

---

## 🎯 KPI #9: CIERRE DE VISITAS Y LOGISTICA

```sql
SELECT 
  f.nombre as fase,
  cvm.nombre as motivo_cierre,
  COUNT(ocv.id) as total_cierres,
  AVG(CASE WHEN ocv.cupo_nuevo > ocv.cupo_anterior THEN ocv.cupo_nuevo - ocv.cupo_anterior ELSE 0 END) as cupo_promedio_aumentado,
  COUNT(DISTINCT ocv.sucursal_cierre) as sucursales_involucradas
FROM op_cierre_visitas ocv
LEFT JOIN cat_fase f ON ocv.fase_nueva = f.id_fase
LEFT JOIN op_cierre_visitas_motivos cvm ON ocv.motivo_cierre = cvm.id
GROUP BY ocv.fase_nueva, cvm.nombre, f.nombre
ORDER BY total_cierres DESC;
```

---

## 🎯 KPI #10: COMPETENCIA Y CAPACITACIÓN

```sql
SELECT 
  nt.titulo as test,
  COUNT(ntr.id) as total_intentos,
  COUNT(DISTINCT ntr.usuario_id) as usuarios_participaron,
  SUM(CASE WHEN ntr.aprobado = 1 THEN 1 ELSE 0 END) as aprobados,
  ROUND(100.0 * SUM(CASE WHEN ntr.aprobado = 1 THEN 1 ELSE 0 END) / COUNT(*), 2) as tasa_aprobacion,
  AVG(ntr.puntaje_obtenido) as puntaje_promedio,
  AVG(ntr.tiempo_total_segundos) as tiempo_promedio_segundos
FROM novedades_tests nt
LEFT JOIN novedades_test_resultados ntr ON nt.id = ntr.test_id
WHERE nt.activo = 1
GROUP BY nt.id, nt.titulo
ORDER BY total_intentos DESC;
```

---

## 🎯 KPI #11: ENGANCHE Y PROGRESO DE USUARIO

```sql
SELECT 
  u.id_usuario,
  u.nombre,
  COUNT(DISTINCT CASE WHEN npu.visto = 1 THEN npu.contenido_id END) as contenido_visto,
  COUNT(DISTINCT CASE WHEN npu.completado = 1 THEN npu.contenido_id END) as contenido_completado,
  COUNT(DISTINCT CASE WHEN npu.favorito = 1 THEN npu.contenido_id END) as favoritos,
  AVG(npu.progreso_porcentaje) as progreso_promedio,
  COUNT(DISTINCT nul.logro_id) as logros_obtenidos,
  MAX(npu.fecha_ultima_vista) as ultima_actividad
FROM adm_usuario u
LEFT JOIN novedades_progreso_usuario npu ON u.id_usuario = npu.usuario_id
LEFT JOIN novedades_usuario_logros nul ON u.id_usuario = nul.usuario_id
GROUP BY u.id_usuario, u.nombre
ORDER BY contenido_completado DESC;
```

---

## 🎯 KPI #12: DISTRIBUCION DE CARGA DE TRABAJO

```sql
SELECT 
  u.nombre,
  u.apellido,
  COUNT(DISTINCT o.id_oportunidad) as oportunidades_activas,
  COUNT(DISTINCT cc.id_call) as llamadas_realizadas,
  DATEDIFF(DAY, u.date_create, GETDATE()) as dias_en_sistema,
  CASE 
    WHEN COUNT(DISTINCT o.id_oportunidad) > 50 THEN 'Alta'
    WHEN COUNT(DISTINCT o.id_oportunidad) > 20 THEN 'Media'
    ELSE 'Baja'
  END as carga_trabajo
FROM adm_usuario u
LEFT JOIN op_oportunidad o ON u.id_usuario = o.id_asesor AND o.estado != 'Cerrada'
LEFT JOIN cli_cliente_call cc ON u.usuario = cc.user_create AND DATE(cc.date_create) >= DATEADD(DAY, -30, GETDATE())
WHERE u.estado = 'activo'
GROUP BY u.id_usuario, u.nombre, u.apellido, u.date_create
ORDER BY oportunidades_activas DESC;
```

---

## 🎯 KPI #13: INTEGRACION SAGA Y FACTURAS

```sql
SELECT 
  DATE(vfsc.fe_creacion) as fecha,
  vfsc.tx_tipodoc as tipo_documento,
  COUNT(*) as total_documentos,
  SUM(CAST(vfsc.vm_total AS DECIMAL(12,2))) as valor_total,
  COUNT(DISTINCT vfsc.id_vendedor) as vendedores,
  COUNT(DISTINCT CASE WHEN vfsc.bd_estado = 'Procesada' THEN vfsc.id END) as procesadas
FROM vent_facturas_saga_cliente vfsc
GROUP BY DATE(vfsc.fe_creacion), vfsc.tx_tipodoc
ORDER BY fecha DESC;
```

---

## 🎯 KPI #14: CONSULTAS EQUIFAX Y ANALISIS CREDITICIO

```sql
SELECT 
  CASE 
    WHEN CAST(ce.score AS INT) >= 700 THEN 'Excelente'
    WHEN CAST(ce.score AS INT) >= 600 THEN 'Bueno'
    WHEN CAST(ce.score AS INT) >= 500 THEN 'Regular'
    ELSE 'Malo'
  END as rating_score,
  COUNT(DISTINCT ce.cedula) as clientes_consultados,
  COUNT(DISTINCT CASE WHEN ce.sujeto_al_dia = 'Si' THEN ce.cedula END) as al_dia,
  AVG(CAST(ce.cuota_estimada_mensual AS DECIMAL(12,2))) as cuota_promedio,
  AVG(CAST(ce.score_sobreendeudamiento AS INT)) as score_sobreendeudamiento_promedio
FROM cli_equifax ce
WHERE ce.fecha_consulta >= DATEADD(MONTH, -3, GETDATE())
GROUP BY 
  CASE 
    WHEN CAST(ce.score AS INT) >= 700 THEN 'Excelente'
    WHEN CAST(ce.score AS INT) >= 600 THEN 'Bueno'
    WHEN CAST(ce.score AS INT) >= 500 THEN 'Regular'
    ELSE 'Malo'
  END;
```

---

## 🎯 KPI #15: DISTRIBUCION GEOGRAFICA (SUCURSALES)

```sql
SELECT 
  s.sucursal,
  COUNT(DISTINCT ocv.id_cliente) as clientes_activos,
  COUNT(DISTINCT ocv.id) as total_cierres_visita,
  SUM(ocv.cupo_nuevo) as cupo_total,
  COUNT(DISTINCT ocv.creado_por) as asesores
FROM adm_sucursales s
LEFT JOIN op_cierre_visitas ocv ON s.id = ocv.sucursal_cierre
WHERE s.estado = 'activo'
GROUP BY s.id, s.sucursal
ORDER BY total_cierres_visita DESC;
```

---

## ⚙️ NOTAS IMPORTANTES

### Consideraciones SQL:
- Algunos campos pueden ser NULL, usar LEFT JOIN donde sea necesario
- Campos calculados pueden usar CAST si están en formato JSON/TEXT
- Las fechas varían en formato según MySQL vs SQL Server
- Algunos campos ENUM necesitan conversion

### Para Implementar:
1. Crear vistas para cada KPI
2. Agregar índices en columnas de filtrado
3. Considerar agregar tabla de hechos consolidada (tabla resumen diaria)
4. Usar job de SQL Server o evento MySQL para actualizar métricas consolidadas

### Dashboard Recomendado:
- Ventas: #1, #2, #3
- Actividad: #4, #5, #12
- Auditoría: #6, #9
- Capacitación: #10, #11
- Operacional: #13, #14, #15

