<?php
/**
 * =====================================================
 * TEST DE BÚSQUEDA RECURSIVA DE MODELOS
 * =====================================================
 * 
 * Este archivo prueba el nuevo sistema de búsqueda recursiva
 * que permite encontrar modelos en cualquier nivel de carpetas.
 */

// Cargar el sistema
require_once 'config/config.php';
require_once 'libs/controller.php';
require_once 'libs/model.php';
require_once 'libs/database.php';

echo "<h2>🔍 Test de Búsqueda Recursiva de Modelos</h2>";

/**
 * Clase de prueba para testear la búsqueda recursiva
 */
class TestRecursivoController extends Controller
{
    public function testBusquedaRecursiva()
    {
        echo "<h3>📋 Casos de Prueba</h3>";
        
        // Caso 1: Modelo en carpeta específica (models/ejemplos/)
        echo "<h4>1. Modelo en carpeta específica</h4>";
        $this->folder = 'ejemplos';
        if ($this->loadModel('ejemplo')) {
            echo "✅ Encontrado: EjemploModel en models/ejemplos/<br>";
        } else {
            echo "❌ No encontrado: EjemploModel en models/ejemplos/<br>";
        }
        
        // Caso 2: Modelo en subcarpeta anidada (si existe)
        echo "<h4>2. Modelo en subcarpeta anidada</h4>";
        $this->folder = 'ejemplos/pr'; // Carpeta que mencionaste
        if ($this->loadModel('pr')) {
            echo "✅ Encontrado: PrModel mediante búsqueda recursiva<br>";
        } else {
            echo "❌ No encontrado: PrModel (normal si no existe)<br>";
        }
        
        // Caso 3: Búsqueda recursiva automática
        echo "<h4>3. Búsqueda recursiva automática</h4>";
        $this->folder = null; // Sin carpeta específica
        if ($this->loadModel('ejemplo')) {
            echo "✅ Encontrado: EjemploModel mediante búsqueda recursiva automática<br>";
        } else {
            echo "❌ No encontrado mediante búsqueda recursiva<br>";
        }
        
        // Caso 4: Modelo en raíz de models/
        echo "<h4>4. Modelo en raíz de models/</h4>";
        if ($this->loadModel('logsmodel')) {
            echo "✅ Encontrado: LogsmodelModel en raíz de models/<br>";
        } else {
            echo "❌ No encontrado: LogsmodelModel en raíz<br>";
        }
    }
    
    public function mostrarEstructuraCarpetas()
    {
        echo "<h3>📁 Estructura de Carpetas de Modelos</h3>";
        $this->listarDirectorio('models', 0);
    }
    
    private function listarDirectorio($dir, $nivel)
    {
        if (!is_dir($dir)) return;
        
        $espacios = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $nivel);
        $archivos = scandir($dir);
        
        foreach ($archivos as $archivo) {
            if ($archivo === '.' || $archivo === '..') continue;
            
            $rutaCompleta = $dir . '/' . $archivo;
            
            if (is_dir($rutaCompleta)) {
                echo $espacios . "📁 $archivo/<br>";
                $this->listarDirectorio($rutaCompleta, $nivel + 1);
            } else if (strpos($archivo, 'model.php') !== false) {
                echo $espacios . "📄 $archivo<br>";
            }
        }
    }
}

// Ejecutar pruebas
try {
    $test = new TestRecursivoController();
    
    echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0;'>";
    $test->mostrarEstructuraCarpetas();
    echo "</div>";
    
    echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0;'>";
    $test->testBusquedaRecursiva();
    echo "</div>";
    
    echo "<h3>✅ Sistema de Búsqueda Recursiva Implementado</h3>";
    echo "<p><strong>Funcionamiento:</strong></p>";
    echo "<ul>";
    echo "<li>🔹 <strong>Prioridad 1:</strong> Carpeta específica definida en \$this->folder</li>";
    echo "<li>🔹 <strong>Prioridad 2:</strong> Búsqueda recursiva automática en todas las subcarpetas</li>";
    echo "<li>🔹 <strong>Prioridad 3:</strong> Raíz de la carpeta models/</li>";
    echo "</ul>";
    
    echo "<p><strong>Ahora puedes crear carpetas anidadas como:</strong></p>";
    echo "<ul>";
    echo "<li>models/ejemplos/pr/subpr/modelo.php</li>";
    echo "<li>models/a/b/c/d/e/modelo.php</li>";
    echo "<li>models/cualquier/estructura/que/quieras/modelo.php</li>";
    echo "</ul>";
    
    echo "<p><strong>Y el sistema los encontrará automáticamente!</strong> 🎉</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error en las pruebas:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
