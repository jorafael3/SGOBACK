-- ============================================================================================================
-- KPI #2A: TIEMPO PROMEDIO DE FILTRO - SOLO DENTRO DEL RANGO DE FECHAS (RECOMENDADO)
-- Descripción: Calcula el tiempo promedio solo para llamadas Y citas dentro del mismo período
-- IMPORTANTE: 
--   - Filtra LLAMADAS del rango de fechas (2025-12-01 al 2025-12-31)
--   - Filtra CITAS del mismo rango de fechas
--   - Solo incluye clientes donde AMBOS (llamada y cita) están en el rango
--   - Relación: citas por id_cliente (user_create está vacío en cli_op_calender)
-- ============================================================================================================
-- Tiempo promedio de filtro hasta generar la cita
SELECT
  US.id_usuario,
  US.nombre as asesor,
  US.usuario as login_asesor,
  -- DATOS DE LLAMADAS (dentro del rango)
  COUNT(DISTINCT cc.id_call) as total_llamadas,
  MIN(cc.date_create) as fecha_primera_llamada,
  MAX(cc.date_create) as fecha_ultima_llamada,
  -- DATOS DE CITAS AGENDADAS (dentro del rango)
  COUNT(DISTINCT coc.id_calender) as total_citas_agendadas,
  MIN(coc.fecha) as fecha_primera_cita,
  MAX(coc.fecha) as fecha_ultima_cita,
  -- TASA DE EFECTIVIDAD
  ROUND(100.0 * COUNT(DISTINCT coc.id_calender) / NULLIF(COUNT(DISTINCT cc.id_call), 0), 2) as tasa_efectividad_porcentaje,
  -- TIEMPO PROMEDIO DE FILTRO (desde primera llamada del período hasta cita)
  ROUND(AVG(DATEDIFF(coc.fecha, primera_llamada_periodo.fecha_primer_contacto)), 2) as dias_promedio_filtro,
  ROUND(AVG(TIMESTAMPDIFF(HOUR, primera_llamada_periodo.fecha_primer_contacto, coc.fecha)), 2) as horas_promedio_filtro
FROM adm_usuario US
-- Citas del rango de fechas
INNER JOIN cli_op_calender coc ON coc.fecha >= '2025-12-01' AND coc.fecha <= '2025-12-31'
-- Llamadas del mismo rango
INNER JOIN cli_cliente_call cc ON cc.id_cliente = coc.id_cliente 
  AND cc.user_create = US.usuario
  AND cc.date_create >= '2025-12-01' AND cc.date_create <= '2025-12-31'
-- Primera llamada del cliente EN EL PERÍODO
INNER JOIN (
  SELECT 
    id_cliente,
    MIN(date_create) as fecha_primer_contacto
  FROM cli_cliente_call
  WHERE date_create >= '2025-12-01' AND date_create <= '2025-12-31'
  GROUP BY id_cliente
) primera_llamada_periodo ON primera_llamada_periodo.id_cliente = coc.id_cliente
WHERE US.rol_id = 3 AND US.estado = 'A'
GROUP BY US.id_usuario, US.nombre, US.usuario
HAVING total_llamadas > 0 AND total_citas_agendadas > 0
ORDER BY total_citas_agendadas DESC;


-- ============================================================================================================
-- KPI #2B: TIEMPO PROMEDIO DE FILTRO - HISTÓRICO (Primera llamada de CUALQUIER fecha)
-- Descripción: Calcula el tiempo desde la PRIMERA llamada histórica hasta la cita agendada
-- IMPORTANTE: 
--   - Las CITAS se filtran por rango de fechas (solo citas de diciembre)
--   - Las LLAMADAS NO se filtran por fecha (captura primera llamada histórica del cliente)
--   - Útil para medir el ciclo completo de conversión desde el primer contacto
-- ============================================================================================================

SELECT
  US.id_usuario,
  US.nombre as asesor,
  US.usuario as login_asesor,
  -- DATOS DE CITAS AGENDADAS (en el rango de fechas)
  COUNT(DISTINCT coc.id_calender) as total_citas_agendadas,
  MIN(coc.fecha) as fecha_primera_cita,
  MAX(coc.fecha) as fecha_ultima_cita,
  -- DATOS DE LLAMADAS (primera llamada histórica de esos clientes)
  COUNT(DISTINCT cc.id_call) as total_llamadas_historicas,
  MIN(cc.date_create) as fecha_primera_llamada_historica,
  MAX(cc.date_create) as fecha_ultima_llamada,
  -- TIEMPO PROMEDIO DE FILTRO (desde primera llamada histórica hasta primera cita)
  ROUND(AVG(DATEDIFF(coc.fecha, primera_llamada.fecha_primer_contacto)), 2) as dias_promedio_filtro,
  ROUND(AVG(TIMESTAMPDIFF(HOUR, primera_llamada.fecha_primer_contacto, coc.fecha)), 2) as horas_promedio_filtro
FROM adm_usuario US
-- Solo citas del rango de fechas
INNER JOIN cli_op_calender coc ON coc.fecha >= '2025-12-01' AND coc.fecha <= '2025-12-31'
-- Todas las llamadas del cliente (sin filtro de fecha)
INNER JOIN cli_cliente_call cc ON cc.id_cliente = coc.id_cliente AND cc.user_create = US.usuario
-- Obtener la PRIMERA llamada HISTÓRICA de cada cliente
INNER JOIN (
  SELECT 
    id_cliente,
    MIN(date_create) as fecha_primer_contacto
  FROM cli_cliente_call
  GROUP BY id_cliente
) primera_llamada ON primera_llamada.id_cliente = coc.id_cliente
WHERE US.rol_id = 3 AND US.estado = 'A'
GROUP BY US.id_usuario, US.nombre, US.usuario
ORDER BY total_citas_agendadas DESC;

-- ============================================================================================================
-- NOTAS
-- ============================================================================================================
-- Este query en un solo SELECT muestra:
-- 1. Total de llamadas (primer contacto)
-- 2. Total de citas agendadas
-- 3. Tasa de efectividad = (Citas / Llamadas) * 100
-- 4. Fechas min/max para cada métrica
-- ============================================================================================================


 -- HISTORIAL DE CLIENTES RECIBIDOS
SELECT
us.id_usuario,
us.nombre as asesor,
us.usuario as login_asesor,
clientes_hist_recibidos.clientes_recibidos,
clientes_hist_perdidos.clientes_perdidos,
oportunidades_hist_recibidas.oportunidades_recibidas,
oportunidades_hist_perdidas.oportunidades_perdidas
FROM adm_usuario us
left outer join (
    select asesor_nuevo as id_asesor, COUNT(*) as clientes_recibidos
    from cli_clientes_asesor_historial 
    where tipo = 'CREACION' 
        and DATE(fecha) BETWEEN '2025-12-01' and '2025-12-31'
    group by asesor_nuevo
) as clientes_hist_recibidos on clientes_hist_recibidos.id_asesor = us.id_usuario
-- HISTORIAL DE CLIENTES PERDIDOS  
left outer join (
    select asesor_anterior as id_asesor, COUNT(*) as clientes_perdidos
    from cli_clientes_asesor_historial 
    where tipo = 'UPDATE' 
        and DATE(fecha) BETWEEN '2025-12-01' and '2025-12-31'
        and asesor_anterior IS NOT NULL
    group by asesor_anterior
) as clientes_hist_perdidos on clientes_hist_perdidos.id_asesor = us.id_usuario
-- HISTORIAL DE OPORTUNIDADES RECIBIDAS
left outer join (
    select asesor_nuevo as id_asesor, COUNT(*) as oportunidades_recibidas
    from op_oportunidad_asesor_historial 
    where tipo = 'CREACION' 
        and DATE(fecha) BETWEEN '2025-12-01' and '2025-12-31'
    group by asesor_nuevo
) as oportunidades_hist_recibidas on oportunidades_hist_recibidas.id_asesor = us.id_usuario
-- HISTORIAL DE OPORTUNIDADES PERDIDAS
left outer join (
    select asesor_anterior as id_asesor, COUNT(*) as oportunidades_perdidas
    from op_oportunidad_asesor_historial 
    where tipo = 'UPDATE' 
        and DATE(fecha) BETWEEN '2025-12-01' and '2025-12-31'
        and asesor_anterior IS NOT NULL
    group by asesor_anterior
) as oportunidades_hist_perdidas on oportunidades_hist_perdidas.id_asesor = us.id_usuario

select * from cat_calidad_cliente;

-- ============================================================================================================
-- KPI #3: CIERRES POSITIVOS Y NEGATIVOS (Calidad del Cliente)
-- Descripción: Cuenta cierres de visitas clasificados como positivos (SUMA) o negativos (RESTA)
-- IMPORTANTE:
--   - Se basa en op_cierre_visitas.id_calidad_cliente
--   - cat_calidad_cliente.operacion define si es positivo (SUMA) o negativo (RESTA)
--   - Solo incluye cierres CON calidad clasificada (omite id_calidad_cliente NULL)
--   - Filtra por fecha de cierre y rol de asesor precalificador (rol_id = 3)
-- ============================================================================================================

SELECT 
  US.id_usuario,
  US.nombre as asesor,
  US.usuario as login_asesor,
  -- TOTALES (solo cierres clasificados)
  COUNT(DISTINCT cv.id) as total_cierres_clasificados,
  -- CIERRES POSITIVOS (operacion = SUMA)
  COUNT(DISTINCT CASE WHEN ccc.operacion = 'SUMA' THEN cv.id END) as cierres_positivos,
  -- CIERRES NEGATIVOS (operacion = RESTA)
  COUNT(DISTINCT CASE WHEN ccc.operacion = 'RESTA' THEN cv.id END) as cierres_negativos,
  -- RATIO DE CALIDAD (Positivos / Negativos)
  ROUND(
    COUNT(DISTINCT CASE WHEN ccc.operacion = 'SUMA' THEN cv.id END) / 
    NULLIF(COUNT(DISTINCT CASE WHEN ccc.operacion = 'RESTA' THEN cv.id END), 0),
    2
  ) as ratio_positivos_negativos,
  -- PORCENTAJES
  ROUND(100.0 * COUNT(DISTINCT CASE WHEN ccc.operacion = 'SUMA' THEN cv.id END) / NULLIF(COUNT(DISTINCT cv.id), 0), 2) as porcentaje_positivos,
  ROUND(100.0 * COUNT(DISTINCT CASE WHEN ccc.operacion = 'RESTA' THEN cv.id END) / NULLIF(COUNT(DISTINCT cv.id), 0), 2) as porcentaje_negativos
FROM adm_usuario US
INNER JOIN op_cierre_visitas cv ON cv.creado_por = US.id_usuario
  AND DATE(cv.fecha_creado) >= '2025-12-01' AND DATE(cv.fecha_creado) <= '2025-12-31'
  AND cv.id_calidad_cliente IS NOT NULL
INNER JOIN cat_calidad_cliente ccc ON ccc.id_calidad_cliente = cv.id_calidad_cliente
WHERE US.rol_id = 3 AND US.estado = 'A'
GROUP BY US.id_usuario, US.nombre, US.usuario
HAVING total_cierres_clasificados > 0
ORDER BY ratio_positivos_negativos DESC, total_cierres_clasificados DESC;

-- ============================================================================================================
-- KPI #4: CIERRES ATENDIDOS Y CONFIRMACIÓN DEL BANCO (Fase 4)
-- Descripción: Cuenta total de cierres atendidos y cuántos llegaron a fase 4 (Confirmación del banco)
-- IMPORTANTE:
--   - Cuenta todos los registros en op_cierre_visitas por asesor
--   - Identifica cuántos tienen fase_nueva = 4 (Confirmación del banco)
--   - Calcula el porcentaje de conversión a fase 4
--   - Filtra por fecha de cierre y rol de asesor precalificador (rol_id = 3)
-- ============================================================================================================

SELECT 
  US.id_usuario,
  US.nombre as asesor,
  US.usuario as login_asesor,
  -- TOTAL DE CIERRES ATENDIDOS
  COUNT(DISTINCT cv.id) as total_cierres_atendidos,
  -- CIERRES CON FASE 4 (Confirmación del banco)
  COUNT(DISTINCT CASE WHEN cv.fase_nueva = 4 THEN cv.id END) as cierres_fase_4_confirmacion,
  -- PORCENTAJE FASE 4
  ROUND(100.0 * COUNT(DISTINCT CASE WHEN cv.fase_nueva = 4 THEN cv.id END) / NULLIF(COUNT(DISTINCT cv.id), 0), 2) as porcentaje_fase_4,
  -- DESGLOSE DE OTRAS FASES (opcional)
  COUNT(DISTINCT CASE WHEN cv.fase_nueva = 1 THEN cv.id END) as fase_1,
  COUNT(DISTINCT CASE WHEN cv.fase_nueva = 3 THEN cv.id END) as fase_3,
  COUNT(DISTINCT CASE WHEN cv.fase_nueva = 5 THEN cv.id END) as fase_5,
  COUNT(DISTINCT CASE WHEN cv.fase_nueva = 7 THEN cv.id END) as fase_7,
  COUNT(DISTINCT CASE WHEN cv.fase_nueva = 9 THEN cv.id END) as fase_9
FROM adm_usuario US
INNER JOIN op_cierre_visitas cv ON cv.creado_por = US.id_usuario
  AND DATE(cv.fecha_creado) >= '2025-12-01' AND DATE(cv.fecha_creado) <= '2025-12-31'
WHERE US.rol_id = 3 AND US.estado = 'A'
GROUP BY US.id_usuario, US.nombre, US.usuario
HAVING total_cierres_atendidos > 0
ORDER BY total_cierres_atendidos DESC;



