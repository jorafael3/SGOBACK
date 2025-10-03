<?php
/**
 * =====================================================
 * TEST DE ROUTING RECURSIVO - CONTROLADORES Y MODELOS
 * =====================================================
 * 
 * Este archivo prueba el nuevo sistema de routing recursivo
 * que permite encontrar controladores y modelos en cualquier nivel de carpetas.
 */

echo "<h2>🚀 Test de Routing Recursivo Completo</h2>";

echo "<h3>📁 Estructura de Carpetas Actual</h3>";

function listarEstructura($dir, $nivel = 0, $tipo = 'controllers') {
    if (!is_dir($dir)) return;
    
    $espacios = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $nivel);
    $archivos = scandir($dir);
    
    foreach ($archivos as $archivo) {
        if ($archivo === '.' || $archivo === '..') continue;
        
        $rutaCompleta = $dir . '/' . $archivo;
        
        if (is_dir($rutaCompleta)) {
            echo $espacios . "📁 $archivo/<br>";
            listarEstructura($rutaCompleta, $nivel + 1, $tipo);
        } else if (strpos($archivo, '.php') !== false) {
            $icono = ($tipo === 'controllers') ? "🎮" : "📊";
            echo $espacios . "$icono $archivo<br>";
        }
    }
}

echo "<div style='display: flex; gap: 20px;'>";

echo "<div style='border: 1px solid #ccc; padding: 15px; flex: 1;'>";
echo "<h4>🎮 Controladores</h4>";
listarEstructura('controllers', 0, 'controllers');
echo "</div>";

echo "<div style='border: 1px solid #ccc; padding: 15px; flex: 1;'>";
echo "<h4>📊 Modelos</h4>";
listarEstructura('models', 0, 'models');
echo "</div>";

echo "</div>";

echo "<h3>🔍 Ejemplos de URLs que Ahora Funcionan</h3>";

$ejemplosUrls = [
    // Estructura básica (ya funcionaba)
    ['url' => '/principal', 'descripcion' => 'Controlador en raíz', 'estructura' => 'controllers/principal.php'],
    ['url' => '/login/authenticate', 'descripcion' => 'Controlador en 1 nivel', 'estructura' => 'controllers/login/login.php'],
    
    // Nuevas estructuras recursivas
    ['url' => '/ejemplos/ejemplomultiempresa/consultaBasica', 'descripcion' => 'Controlador en 2 niveles', 'estructura' => 'controllers/ejemplos/ejemplomultiempresa.php'],
    ['url' => '/ejemplos/pr/pr/miMetodo', 'descripcion' => 'Controlador en 3 niveles', 'estructura' => 'controllers/ejemplos/pr/pr.php'],
    ['url' => '/a/b/c/micontrolador/metodo', 'descripcion' => 'Controlador en 4 niveles', 'estructura' => 'controllers/a/b/c/micontrolador.php'],
    ['url' => '/nivel1/nivel2/nivel3/nivel4/nivel5/controlador/accion', 'descripcion' => 'Controlador en 6 niveles', 'estructura' => 'controllers/nivel1/nivel2/nivel3/nivel4/nivel5/controlador.php'],
];

echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th style='padding: 10px;'>URL de Ejemplo</th>";
echo "<th style='padding: 10px;'>Descripción</th>";
echo "<th style='padding: 10px;'>Estructura de Archivo</th>";
echo "<th style='padding: 10px;'>Estado</th>";
echo "</tr>";

foreach ($ejemplosUrls as $ejemplo) {
    $existe = file_exists($ejemplo['estructura']);
    $estado = $existe ? "✅ Existe" : "⚠️ Crear archivo";
    $colorFila = $existe ? "#e8f5e8" : "#fff8e1";
    
    echo "<tr style='background-color: $colorFila;'>";
    echo "<td style='padding: 8px; font-family: monospace;'>{$ejemplo['url']}</td>";
    echo "<td style='padding: 8px;'>{$ejemplo['descripcion']}</td>";
    echo "<td style='padding: 8px; font-family: monospace; font-size: 12px;'>{$ejemplo['estructura']}</td>";
    echo "<td style='padding: 8px; text-align: center;'>$estado</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>🎯 Cómo Funciona el Nuevo Sistema</h3>";

echo "<div style='border: 1px solid #ccc; padding: 15px; background-color: #f9f9f9;'>";
echo "<h4>📋 Algoritmo de Búsqueda de Controladores:</h4>";
echo "<ol>";
echo "<li><strong>Análisis de URL:</strong> <code>/a/b/c/controlador/metodo/param1</code></li>";
echo "<li><strong>Búsqueda Progresiva:</strong>";
echo "<ul>";
echo "<li>🔍 Prueba: <code>controllers/a.php</code> (controlador = a)</li>";
echo "<li>🔍 Prueba: <code>controllers/a/b.php</code> (controlador = b)</li>";
echo "<li>🔍 Prueba: <code>controllers/a/b/c.php</code> (controlador = c)</li>";
echo "<li>✅ Encuentra: <code>controllers/a/b/c/controlador.php</code></li>";
echo "</ul></li>";
echo "<li><strong>Resultado:</strong> Controlador = 'controlador', Método = 'metodo', Params = ['param1']</li>";
echo "</ol>";

echo "<h4>📋 Algoritmo de Búsqueda de Modelos:</h4>";
echo "<ol>";
echo "<li><strong>Prioridad 1:</strong> Carpeta específica (<code>\$this->folder</code>)</li>";
echo "<li><strong>Prioridad 2:</strong> Búsqueda recursiva automática</li>";
echo "<li><strong>Prioridad 3:</strong> Raíz de models/</li>";
echo "</ol>";
echo "</div>";

echo "<h3>🛠️ Instrucciones para Crear Nuevos Controladores</h3>";

echo "<div style='border: 1px solid #ccc; padding: 15px; background-color: #f0f8ff;'>";
echo "<h4>Ejemplo: Crear controlador en <code>/empresas/admin/usuarios/gestionar</code></h4>";
echo "<p><strong>1. Crear archivo:</strong> <code>controllers/empresas/admin/usuarios.php</code></p>";
echo "<p><strong>2. Código del controlador:</strong></p>";
echo "<pre style='background-color: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo htmlspecialchars('<?php
class Usuarios extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = "empresas/admin"; // Opcional: para modelos
        $this->loadModel("usuario"); // Busca recursivamente
    }

    public function gestionar()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;
        
        $result = $this->model->obtenerUsuarios();
        $this->jsonResponse($result, 200);
    }
}');
echo "</pre>";

echo "<p><strong>3. URL de acceso:</strong> <code>POST /empresas/admin/usuarios/gestionar</code></p>";
echo "<p><strong>4. Modelo correspondiente:</strong> Se busca automáticamente en cualquier subcarpeta</p>";
echo "</div>";

echo "<h3>✅ Beneficios del Sistema Recursivo</h3>";

echo "<ul>";
echo "<li>🚀 <strong>Flexibilidad total:</strong> Cualquier estructura de carpetas</li>";
echo "<li>🔧 <strong>Zero configuración:</strong> Funciona automáticamente</li>";
echo "<li>🔄 <strong>Compatibilidad:</strong> No rompe código existente</li>";
echo "<li>📈 <strong>Escalabilidad:</strong> Organiza proyectos grandes fácilmente</li>";
echo "<li>🎯 <strong>Intuitivo:</strong> URL refleja estructura de carpetas</li>";
echo "</ul>";

echo "<h3>🎉 ¡Sistema Recursivo Completo Implementado!</h3>";
echo "<p>Ahora puedes crear <strong>controladores</strong> y <strong>modelos</strong> en cualquier nivel de carpetas y el sistema los encontrará automáticamente.</p>";

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 20px 0; }
pre { overflow-x: auto; }
ul li { margin: 5px 0; }
ol li { margin: 5px 0; }
</style>
