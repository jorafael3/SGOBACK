<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';


class Usuarios extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'mantenimiento/usuarios/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('usuarios'); // Cargar el modelo correcto
    }



    function GetUsuarios()
    {

        echo  json_encode("HOLAAA");
        exit;

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);
        // exit;

        $result = $this->model->getUsuarios($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener facturas guías pickup',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function GetDepartamentosLogistica()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $result = ["success" => true, "data" => []];

        if (strtoupper($data["empresa"]) == "CARTIMEX") {
            $result = $this->model->getDepartamentosLogistica();
        }

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener GetDepartamentosLogistica',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function CrearUsuario()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();



        $VALIDAR_USUARIO = $this->model->ValidarUsuarioCreado($data);

        if ($VALIDAR_USUARIO && $VALIDAR_USUARIO['success'] && count($VALIDAR_USUARIO['data']) > 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'El usuario ya existe',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $VALIDAR_USUARIO
            ], 200);
            return;
        }
        $CrearUsuario = $this->model->CrearUsuario($data);

        if ($CrearUsuario && $CrearUsuario['success']) {
            $CrearUsuario['message'] = 'Usuario creado exitosamente';
            $this->jsonResponse($CrearUsuario, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al crear usuario',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $CrearUsuario
            ], 200);
        }
    }

    function ActualizarUsuario()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $usrid = $data['usrid'] ?? '';
        $menus_selecciondos = $data['menus'] ?? [];
        // Procesar los menús seleccionados (solo los checked = true)
        $menusSeleccionados = $this->extraerMenusSeleccionados($menus_selecciondos);
        // Actualizar los datos con los menús procesados
        $data['menus_sel'] = $menusSeleccionados;
        // echo json_encode($data);
        // exit;

        $ActualizarUsuario = $this->model->ActualizarUsuario($data);
        $ActualizarMenus = $this->model->ActualizarMenusUsuario($usrid, $menusSeleccionados);
        if ($ActualizarUsuario && $ActualizarUsuario['success']) {
            $ActualizarUsuario['message'] = 'Usuario actualizado exitosamente';
            $this->jsonResponse($ActualizarUsuario, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al actualizar usuario',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $ActualizarUsuario
            ], 200);
        }
    }

    public function GetMenuUsuario()
    {

        // echo json_encode("asdasd");
        // exit;

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $usrid = $data['sessionData']['usrid'] ?? '';
        $empleado_empresa = $data['sessionData']['empleado_empresa'] ?? '';

        $result = $this->model->getMenuUsuario($usrid, $empleado_empresa);
        // echo json_encode($result);
        // exit;
        if ($result && $result['success']) {
            // Convertir estructura plana a jerárquica
            $menuJerarquico = $this->construirMenuJerarquico($result['data']);

            $this->jsonResponse([
                'success' => true,
                'data' => $menuJerarquico,
                "respuesta" => $result
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener el menú del usuario',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    /**
     * Construye el menú jerárquico a partir de la estructura plana
     */
    private function construirMenuJerarquico($menuPlano)
    {
        // Organizar items por ID para acceso rápido
        $itemsPorId = [];
        foreach ($menuPlano as $item) {
            $itemsPorId[$item['MenuId']] = [
                'id' => strtolower(str_replace(' ', '', $item['Titulo'])),
                'title' => $item['Titulo'],
                'type' => $item['Type'],
                'path' => $item['Path'],
                'icon' => $item['Icono'],
                'active' => $item['Asignado'] == '1',
                'level' => 1,
                'badge' => false
            ];

            // Agregar propiedades específicas según el tipo
            if ($item['Type'] == 'main_title') {
                $itemsPorId[$item['MenuId']]['main_title'] = $item['Titulo'];
                unset($itemsPorId[$item['MenuId']]['title']);
            }

            // Agregar badge si es necesario (puedes personalizar esto)
            if ($item['Type'] == 'sub' && $item['PadreId'] === null) {
                $itemsPorId[$item['MenuId']]['badge'] = true;
                $itemsPorId[$item['MenuId']]['badge_value'] = '3';
                $itemsPorId[$item['MenuId']]['badge_color'] = 'primary';
            }

            $itemsPorId[$item['MenuId']]['_padreid'] = $item['PadreId'];
            $itemsPorId[$item['MenuId']]['_orden'] = $item['Orden'];
        }

        // Construir la jerarquía
        $menuFinal = [];

        foreach ($menuPlano as $item) {
            $menuItem = &$itemsPorId[$item['MenuId']];

            if ($item['PadreId'] === null || $item['PadreId'] === '') {
                // Es un elemento raíz
                $menuFinal[] = &$menuItem;
            } else {
                // Es un hijo, agregarlo al padre
                if (isset($itemsPorId[$item['PadreId']])) {
                    if (!isset($itemsPorId[$item['PadreId']]['children'])) {
                        $itemsPorId[$item['PadreId']]['children'] = [];
                    }
                    $itemsPorId[$item['PadreId']]['children'][] = &$menuItem;
                }
            }
        }

        // Limpiar propiedades temporales y ordenar
        $this->limpiarYOrdenarMenu($menuFinal);

        return $menuFinal;
    }

    /**
     * Limpia propiedades temporales y ordena el menú recursivamente
     */
    private function limpiarYOrdenarMenu(&$menu)
    {
        foreach ($menu as &$item) {
            // Ordenar hijos si existen
            if (isset($item['children'])) {
                usort($item['children'], function ($a, $b) {
                    return intval($a['_orden']) - intval($b['_orden']);
                });
                $this->limpiarYOrdenarMenu($item['children']);
            }

            // Eliminar propiedades temporales
            unset($item['_padreid']);
            unset($item['_orden']);
        }

        // Ordenar el nivel actual
        usort($menu, function ($a, $b) {
            $ordenA = isset($a['_orden']) ? intval($a['_orden']) : 0;
            $ordenB = isset($b['_orden']) ? intval($b['_orden']) : 0;
            return $ordenA - $ordenB;
        });
    }

    public function GetMenuUsuarioAsignacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $data = $this->getJsonInput();
        $usuarioId = $data['usrid'] ?? '';
        $empresa = $data['empresa'] ?? '';

        $result = $this->model->getMenuUsuarioAsignacion($usuarioId, $empresa);
        // echo json_encode($result);
        // exit;

        if ($result && $result['success']) {
            // Convertir estructura plana a jerárquica con asignación
            $menuJerarquico = $this->construirMenuJerarquicoConAsignacion($result['data']);

            $this->jsonResponse([
                'success' => true,
                'data' => $menuJerarquico,
                "respuesta" => $data,
                "empresa" => $empresa
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener el menú para asignación',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    /**
     * Construye el menú jerárquico con información de asignación para checkboxes
     */
    private function construirMenuJerarquicoConAsignacion($menuPlano)
    {
        // Organizar items por ID para acceso rápido
        $itemsPorId = [];
        foreach ($menuPlano as $item) {
            $itemsPorId[$item['MenuId']] = [
                'menu_id' => $item['MenuId'],
                'id' => strtolower(str_replace(' ', '', $item['Titulo'])),
                'title' => $item['Titulo'],
                'type' => $item['Type'],
                'path' => $item['Path'],
                'icon' => $item['Icono'],
                'asignado' => $item['Asignado'] == '1',
                'checked' => $item['Asignado'] == '1', // Para los checkboxes
                'level' => 1,
                'badge' => false
            ];

            // Agregar propiedades específicas según el tipo
            if ($item['Type'] == 'main_title') {
                $itemsPorId[$item['MenuId']]['main_title'] = $item['Titulo'];
                unset($itemsPorId[$item['MenuId']]['title']);
            }

            // Agregar badge si es necesario
            if ($item['Type'] == 'sub' && ($item['PadreId'] === null || $item['PadreId'] === '')) {
                $itemsPorId[$item['MenuId']]['badge'] = true;
                $itemsPorId[$item['MenuId']]['badge_value'] = '0';
                $itemsPorId[$item['MenuId']]['badge_color'] = 'primary';
            }

            $itemsPorId[$item['MenuId']]['_padreid'] = $item['PadreId'];
            $itemsPorId[$item['MenuId']]['_orden'] = $item['Orden'];
        }

        // Construir la jerarquía
        $menuFinal = [];

        foreach ($menuPlano as $item) {
            $menuItem = &$itemsPorId[$item['MenuId']];

            if ($item['PadreId'] === null || $item['PadreId'] === '') {
                // Es un elemento raíz
                $menuFinal[] = &$menuItem;
            } else {
                // Es un hijo, agregarlo al padre
                if (isset($itemsPorId[$item['PadreId']])) {
                    if (!isset($itemsPorId[$item['PadreId']]['children'])) {
                        $itemsPorId[$item['PadreId']]['children'] = [];
                    }
                    $itemsPorId[$item['PadreId']]['children'][] = &$menuItem;
                }
            }
        }

        // Limpiar propiedades temporales y ordenar
        $this->limpiarYOrdenarMenuAsignacion($menuFinal);

        return $menuFinal;
    }

    /**
     * Limpia propiedades temporales y ordena el menú de asignación recursivamente
     */
    private function limpiarYOrdenarMenuAsignacion(&$menu)
    {
        foreach ($menu as &$item) {
            // Ordenar hijos si existen
            if (isset($item['children'])) {
                usort($item['children'], function ($a, $b) {
                    return intval($a['_orden']) - intval($b['_orden']);
                });
                $this->limpiarYOrdenarMenuAsignacion($item['children']);
            }

            // Eliminar propiedades temporales
            unset($item['_padreid']);
            unset($item['_orden']);
        }

        // Ordenar el nivel actual
        usort($menu, function ($a, $b) {
            $ordenA = isset($a['_orden']) ? intval($a['_orden']) : 0;
            $ordenB = isset($b['_orden']) ? intval($b['_orden']) : 0;
            return $ordenA - $ordenB;
        });
    }

    /**
     * Extrae recursivamente los menús seleccionados (checked = true) de la estructura jerárquica
     * Devuelve un array con solo los 'id' de los items con checked = true
     */
    private function extraerMenusSeleccionados($menuArray)
    {
        $seleccionados = [];

        foreach ($menuArray as $item) {
            // Si el item tiene checked = true, agregarlo al array
            if (isset($item['checked']) && $item['checked'] === true) {
                $seleccionados[] = $item['menu_id'];
            }

            // Procesar hijos recursivamente si existen
            if (isset($item['children']) && is_array($item['children'])) {
                $hijosSeleccionados = $this->extraerMenusSeleccionados($item['children']);
                // Merge con los hijos encontrados
                $seleccionados = array_merge($seleccionados, $hijosSeleccionados);
            }
        }

        return $seleccionados;
    }
}
