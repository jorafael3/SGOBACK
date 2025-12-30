<?php
require_once __DIR__ . '../../../libs/JwtHelper.php';
class MPPs extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'oym/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('mpps'); // Cargar el modelo correcto
    }

    private function sanitizeSegment(string $p): string
    {
        $p = trim($p);
        if ($p === '' || $p === '.' || $p === '..')
            return '';
        // Cambia cualquier char que no sea [A-Za-z0-9_-] por "_"
        return preg_replace('/[^A-Za-z0-9_\-]/', '_', $p);
    }

    private function normalizeDept($dept): string
    {
        $dept = str_replace(['\\', '/'], '_', $dept);
        $dept = preg_replace('/[:*?"<>|]/', '_', $dept);
        $dept = $this->sanitizeSegment($dept);
        return $dept !== '' ? $dept : 'SIN_DEPARTAMENTO';
    }

    private function resolveDestinationFolder($rawSubpath, array $data = [])
    {

        $SO = PHP_OS;

        // Determinar la empresa desde userdata (soporta varios nombres de campo)
        $companyRaw = strtolower(trim((string) ($data['userdata']['empleado_empresa'] ?? 'Cartimex')));
        $sucursalRaw = strtolower(trim((string) ($data['userdata']['SUCURSAL_NOMBRE'] ?? '')));

        // Mapeo simple de empresas a carpetas en disco. Añadir más entradas si es necesario.
        $companyMap = [
            'computron' => 'Computronsa',
            'cartimex' => 'Cartimex'
        ];
        $companyFolder = 'Cartimex';
        foreach ($companyMap as $k => $v) {
            if (strpos($companyRaw, $k) !== false) {
                $companyFolder = $v;
                break;
            }
        }

        $companyFolderPath = $companyFolder;
        if (stripos($companyFolder, 'Computron') !== false) {
             $branchSafe = $this->sanitizeSegment($sucursalRaw);
        if ($branchSafe !== '') {
            $companyFolderPath .= DIRECTORY_SEPARATOR . $branchSafe;
        }
    }

        if (stripos($SO, 'Linux') !== false) {
            $baseUpload = '/var/www/html/sgo_docs/' . $companyFolderPath . '/oym/mpps';
        } else {
            // Usar separadores compatibles en Windows; las rutas con '/' funcionan también en PHP/Windows
            $baseUpload = 'C:/xampp/htdocs/sgo_docs/' . $companyFolderPath . '/oym/mpps';
        }


        $baseUpload = rtrim($baseUpload, DIRECTORY_SEPARATOR);
        if (!file_exists($baseUpload)) {
            mkdir($baseUpload, 0777, true);
        }

        $realBase = realpath($baseUpload);
        if ($realBase === false) {
            return [
                'success' => false,
                'message' => 'Base de subida inválida.'
            ];
        }

        $isAdmin = ((string) ($data['userdata']['is_admin'] ?? '0')) === '1';

        $rawSubpath = trim((string) $rawSubpath, "\\/ \t\n\r\0\x0B");
        $segments = [];
        if ($rawSubpath !== '') {
            foreach (preg_split('#[\\/]+#', $rawSubpath) as $p) {
                $safe = $this->sanitizeSegment($p);
                if ($safe !== '')
                    $segments[] = $safe;
            }
        }

        if (empty($segments)) {
            return [
                'success' => true,
                'path' => $realBase,
                'realPath' => $realBase,
                'base' => $realBase,
                'relativePath' => '',
                'isAdmin' => $isAdmin,
                'module' => ''
            ];
        }
        $module = array_shift($segments);
        $allowedModules = ['manuales_de_funciones', 'politicas', 'procedimientos', 'contratos'];
        if (!in_array($module, $allowedModules, true)) {
            return ['success' => false, 'message' => 'Módulo inválido.'];
        }

        $scoped = [$module];

        if ($isAdmin) {
            $scoped = array_merge($scoped, $segments);
        } else {
            $dept = $this->normalizeDept($data['userdata']['EMPLEADO_DEPARTAMENTO_NOMBRE']);
            if (empty($segments)) {
                $scoped[] = $dept;
            } else {
                if ($segments[0] !== $dept) {
                    array_unshift($segments, $dept);
                }
                $scoped = array_merge($scoped, $segments);
            }
        }

        $relativePath = implode(DIRECTORY_SEPARATOR, $scoped);
        $destinationFolder = $realBase . ($relativePath !== '' ? DIRECTORY_SEPARATOR . $relativePath : '');

        // $realDest = realpath($destinationFolder);
        $realDest = file_exists($destinationFolder) ? realpath($destinationFolder) : null;
        if ($realDest !== false && $realDest !== null && strpos($realDest, $realBase) !== 0) {
            return [
                'success' => false,
                'message' => 'Ruta de destino fuera de la base permitida.'
            ];
        }

        return [
            'success' => true,
            'path' => $destinationFolder,
            'realPath' => $realDest,
            'base' => $realBase,
            'relativePath' => $relativePath,
            'isAdmin' => $isAdmin
        ];
    }

    public function CargarCarpetaMPPs()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $data = $this->getJsonInput();
        $body = json_decode(file_get_contents('php://input'), true);
        $rawSubpath = (json_last_error() === JSON_ERROR_NONE && is_array($body))
            ? ($body['subpath'] ?? '')
            : ($_POST['subpath'] ?? '');

        $res = $this->resolveDestinationFolder($rawSubpath, $data);

        if (!$res['success']) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $res['message']
            ], 400);
        }

        $realPath = $res['realPath'];
        $base = $res['base'];
        $currentRelative = $res['relativePath'] ?? '';
        $isAdmin = $res['isAdmin'];

        $metaIndex = $this->loadMetaIndex($base);
        $files = [];

        $currentRelative = str_replace('\\', '/', $currentRelative);

        $parts = array_values(array_filter(explode('/', $currentRelative)));

        // ADMIN:
        if ($isAdmin && count($parts) === 1) {
            $module = $parts[0];
            $this->seedDepartmentFolders($base, $module);
            $moduleDir = $res['realPath'] ?? ($base . DIRECTORY_SEPARATOR . $module);
            if (!is_dir($moduleDir)) {
                $moduleDir = $base . DIRECTORY_SEPARATOR . $module;
            }
            if (!is_dir($moduleDir)) {
                return $this->jsonResponse(['success' => true, 'folders' => [], 'files' => [], 'currentPath' => $currentRelative]);
            }
            $items = array_values(array_diff(scandir($moduleDir), ['.', '..', '.mpps_meta.json']));
            $folders = [];
            foreach ($items as $it) {
                $full = $moduleDir . DIRECTORY_SEPARATOR . $it;
                if (is_dir($full)) {
                    $folders[] = ['name' => $it, 'path' => $module . '/' . $it];
                }
            }
            return $this->jsonResponse(['success' => true, 'folders' => $folders, 'files' => [], 'currentPath' => $currentRelative]);
        }

        if ($res['realPath'] === false || !is_dir($res['realPath'])) {
            @mkdir($res['path'], 0777, true);
        }

        if ($realPath === false || !is_dir($realPath)) {
            return $this->jsonResponse([
                'success' => true,
                'folders' => [],
                'files' => [],
                'path' => $res['path'],
                'currentPath' => $currentRelative
            ]);
        }

        $items = array_values(array_diff(scandir($realPath), ['.', '..', '.mpps_meta.json']));
        $folders = [];
        $files = [];

        foreach ($items as $it) {
            if ($it === '.mpps_meta.json')
                continue;

            $full = $realPath . DIRECTORY_SEPARATOR . $it;
            $relPath = $currentRelative !== '' ? ($currentRelative . '/' . $it) : $it;
            $relPath = str_replace('\\', '/', $relPath);

            if (is_dir($full)) {
                $folders[] = ['name' => $it, 'path' => $relPath];
                continue;
            }
            if (!file_exists($full) || !is_file($full))
                continue;

            $meta = $metaIndex[$relPath] ?? [];
            $files[] = [
                'name' => $meta['name'] ?? $it,
                'title' => $meta['title'] ?? $it,
                'version' => $meta['version'] ?? '',
                'fInicio' => $meta['fInicio'] ?? '',
                'fFin' => $meta['fFin'] ?? '',
                'path' => $relPath,
                'size' => filesize($full),
                'modified' => date('Y-m-d H:i:s', filemtime($full)),
                'extension' => pathinfo($it, PATHINFO_EXTENSION) ?? '',
            ];
        }

        return $this->jsonResponse([
            'success' => true,
            'folders' => $folders,
            'files' => $files,
            'path' => $realPath,
            'currentPath' => $currentRelative
        ]);
    }

    public function CrearCarpetaMPPs()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $body = json_decode(file_get_contents('php://input'), true);
        $subpath = (json_last_error() === JSON_ERROR_NONE && is_array($body))
            ? ($body['subpath'] ?? '')
            : ($_POST['subpath'] ?? '');

        if (!$subpath || trim($subpath) === '') {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Nombre de carpeta requerido'
            ], 400);
        }
        $data = $this->getJsonInput();
        $isAdmin = ((string) ($data['userdata']['is_admin'] ?? '0')) === '1';
        if (!$isAdmin) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'No tiene permisos para crear carpetas.'
            ], 403);
        }
        if ($isAdmin) {
            $raw = trim($subpath, "\\/ \t\n\r\0\x0B");
            $parts = preg_split('#[\\/]+#', $raw);
            $parts = array_values(array_filter(array_map(function ($p) {
                $p = trim($p);
                return ($p === '' || $p === '.' || $p === '..') ? '' : preg_replace('/[^A-Za-z0-9_\-]/', '_', $p);
            }, $parts)));

            $allowedModules = ['manuales_de_funciones', 'politicas', 'procedimientos', 'contratos'];
            if (!isset($parts[0]) || !in_array($parts[0], $allowedModules, true)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Módulo inválido para crear carpeta.'
                ], 400);
            }
            $subpath = implode('/', $parts);
        }
        $res = $this->resolveDestinationFolder($subpath, $data);
        if (!$res['success']) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $res['message']
            ], 400);
        }

        $destinationFolder = $res['path'];
        $realBase = $res['base'];

        if (file_exists($destinationFolder)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'La carpeta ya existe.'
            ], 409);
        }

        if (!mkdir($destinationFolder, 0777, true) && !is_dir($destinationFolder)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'No se pudo crear la carpeta en el servidor.'
            ], 500);
        }

        $realDest = realpath($destinationFolder);
        if ($realDest === false || strpos($realDest, $realBase) !== 0) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Ruta de destino fuera de la base permitida.'
            ], 400);
        }

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Carpeta creada correctamente',
            'path' => str_replace($realBase . DIRECTORY_SEPARATOR, '', $realDest),
            'fullPath' => $realDest
        ]);
    }

    public function EliminarCarpetaMPPs()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true);
        $rawSubpath = (json_last_error() === JSON_ERROR_NONE && is_array($body))
            ? ($body['subpath'] ?? '')
            : ($_POST['subpath'] ?? '');

        if (!$rawSubpath || trim($rawSubpath) === '') {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Ruta de carpeta requerida'
            ], 400);
        }
        $data = $this->getJsonInput();
        $res = $this->resolveDestinationFolder($rawSubpath, $data);
        if (!$res['success']) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $res['message'] ?? 'Error al resolver la ruta'
            ], 400);
        }

        $realPath = $res['realPath'];
        $realBase = $res['base'];
        $relativePath = $res['relativePath'] ?? '';

        if ($realPath === false || !is_dir($realPath)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Carpeta no encontrada'
            ], 404);
        }

        if ($realPath === $realBase) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'No se puede eliminar la carpeta base.'
            ], 403);
        }

        $rrmdir = function ($dir) use (&$rrmdir) {
            if (!is_dir($dir)) {
                return false;
            }
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object === '.' || $object === '..') {
                    continue;
                }
                $full = $dir . DIRECTORY_SEPARATOR . $object;
                if (is_dir($full)) {
                    if (!$rrmdir($full)) {
                        return false;
                    }
                } else {
                    if (!unlink($full)) {
                        return false;
                    }
                }
            }
            return rmdir($dir);
        };

        if (!$rrmdir($realPath)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al eliminar la carpeta'
            ], 500);
        }

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Carpeta eliminada correctamente',
            'path' => str_replace($realBase . DIRECTORY_SEPARATOR, '', $realPath)
        ]);
    }

    public function GuardarArchivoMPPs()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        if (!isset($_FILES['file']) || empty($_FILES['file']['name'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'No se recibió ningún archivo.'
            ], 400);
        }

        $subpath = $_POST['subpath'] ?? '';
        $metaRaw = $_POST['metadata'] ?? '';
        $metadata = [];

        if ($metaRaw) {
            $tmp = json_decode($metaRaw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
                $metadata = $tmp;
            }
        }

        $data = [];
        if (!empty($_POST['userdata'])) {
            $tmp = json_decode($_POST['userdata'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) {
                $data['userdata'] = $tmp;
            }
        }
        $res = $this->resolveDestinationFolder($subpath, $data);
        if (
            !$res['success'] ||
            empty($res['realPath']) ||
            !is_dir($res['realPath'])
        ) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Carpeta destino no válida.'
            ], 400);
        }

        $base = $res['base'];
        $destDir = $res['realPath'];

        $original = $_FILES['file']['name'];
        $info = pathinfo($original);
        $ext = $info['extension'] ?? '';
        $baseName = $info['filename'] ?? 'archivo';

        $safeBase = preg_replace('/[^A-Za-z0-9_\-]/', '_', $baseName);
        if ($safeBase === '')
            $safeBase = 'archivo';

        $finalName = $ext ? ($safeBase . '.' . $ext) : $safeBase;
        $fullPath = $destDir . DIRECTORY_SEPARATOR . $finalName;

        if (file_exists($fullPath)) {
            $unique = uniqid($safeBase . '_', true);
            $finalName = $ext ? ($unique . '.' . $ext) : $unique;
            $fullPath = $destDir . DIRECTORY_SEPARATOR . $finalName;
        }

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $fullPath)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error al mover el archivo.'
            ], 500);
        }

        $realFile = realpath($fullPath);
        if ($realFile === false || strpos($realFile, $base) !== 0) {
            if (is_file($fullPath)) {
                unlink($fullPath);
            }
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Ruta de archivo fuera de la base permitida.'
            ], 400);
        }

        $relative = str_replace($base . DIRECTORY_SEPARATOR, '', $realFile);
        $relative = str_replace('\\', '/', $relative);

        $metaIndex = $this->loadMetaIndex($base);
        $metaIndex[$relative] = [
            'name' => $metadata['name'] ?? $finalName,
            'title' => $metadata['title'] ?? $finalName,
            'version' => $metadata['version'] ?? '',
            'fInicio' => $metadata['fInicio'] ?? '',
            'fFin' => $metadata['fFin'] ?? ''
        ];
        $this->saveMetaIndex($base, $metaIndex);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Archivo guardado correctamente.',
            'file' => $finalName,
            'path' => $relative,
            'name' => $metaIndex[$relative]['name'],
            'title' => $metaIndex[$relative]['title'],
            'version' => $metaIndex[$relative]['version'],
            'fInicio' => $metaIndex[$relative]['fInicio'] ?? '',
            'fFin' => $metaIndex[$relative]['fFin'] ?? '',
            'size' => filesize($realFile),
            'modified' => date('Y-m-d H:i:s', filemtime($realFile))
        ]);
    }

    public function EliminarArchivoMPPs()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $body = json_decode(file_get_contents('php://input'), true);
        $rawPath = (json_last_error() === JSON_ERROR_NONE && is_array($body))
            ? ($body['path'] ?? '')
            : ($_POST['path'] ?? '');

        $rawPath = trim((string) $rawPath, "\\/ \t\n\r\0\x0B");

        if ($rawPath === '') {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Ruta de archivo requerida.'
            ], 400);
        }

        $normalized = str_replace('\\', '/', $rawPath);

        $dir = trim(dirname($normalized), '/');
        $file = basename($normalized);

        $data = $this->getJsonInput();
        $res = $this->resolveDestinationFolder($dir === '.' ? '' : $dir, $data);

        if (
            !$res['success'] ||
            empty($res['realPath']) ||
            !is_dir($res['realPath'])
        ) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Carpeta contenedora no válida.'
            ], 400);
        }

        $base = $res['base'];
        $fullPath = $res['realPath'] . DIRECTORY_SEPARATOR . $file;
        $realFile = realpath($fullPath);

        if (
            $realFile === false ||
            strpos($realFile, $base) !== 0 ||
            !is_file($realFile)
        ) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Archivo no encontrado.'
            ], 404);
        }

        if (!unlink($realFile)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'No se pudo eliminar el archivo.'
            ], 500);
        }

        $relative = str_replace($base . DIRECTORY_SEPARATOR, '', $realFile);
        $relative = str_replace('\\', '/', $relative);

        $metaIndex = $this->loadMetaIndex($base);
        if (isset($metaIndex[$relative])) {
            unset($metaIndex[$relative]);
            $this->saveMetaIndex($base, $metaIndex);
        }

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Archivo eliminado correctamente.',
            'path' => $relative
        ]);
    }

    public function EditarArchivoMPPs()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $body = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($body)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Datos inválidos.'
            ], 400);
        }

        $rawPath = trim((string) ($body['path'] ?? ''), "\\/ \t\n\r\0\x0B");
        $rawNewPath = trim((string) ($body['newPath'] ?? ''), "\\/ \t\n\r\0\x0B");
        $meta = $body['metadata'] ?? [];

        if ($rawPath === '') {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Ruta actual requerida.'
            ], 400);
        }
        if ($rawNewPath === '') {
            $rawNewPath = $rawPath;
        }

        $path = str_replace('\\', '/', $rawPath);
        $newPath = str_replace('\\', '/', $rawNewPath);

        $dir = trim(dirname($path), '/');
        $file = basename($path);

        $newDir = trim(dirname($newPath), '/');
        $newFile = basename($newPath);

        $data = $this->getJsonInput();
        $resSrc = $this->resolveDestinationFolder($dir === '' || $dir === '.' ? '' : $dir, $data);
        if (!$resSrc['success'] || empty($resSrc['realPath']) || !is_dir($resSrc['realPath'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Carpeta origen no válida.'
            ], 400);
        }

        $base = $resSrc['base'];
        $srcFull = $resSrc['realPath'] . DIRECTORY_SEPARATOR . $file;
        $realSrc = realpath($srcFull);

        if ($realSrc === false || strpos($realSrc, $base) !== 0 || !is_file($realSrc)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Archivo original no encontrado.'
            ], 404);
        }

        $data = $this->getJsonInput();
        $resDst = $this->resolveDestinationFolder($newDir === '' || $newDir === '.' ? '' : $newDir, $data);
        if (!$resDst['success'] || empty($resDst['path']) || !is_dir($resDst['path'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Carpeta destino no válida.'
            ], 400);
        }

        $dstDir = $resDst['path'];

        $infoNew = pathinfo($newFile);
        $baseNew = preg_replace('/[^A-Za-z0-9_\-]/', '_', $infoNew['filename'] ?? '');
        if ($baseNew === '') {
            $baseNew = pathinfo($realSrc, PATHINFO_FILENAME);
        }
        $extNew = $infoNew['extension'] ?? pathinfo($realSrc, PATHINFO_EXTENSION);
        $extNew = $extNew ?: '';

        $finalNewName = $extNew ? ($baseNew . '.' . $extNew) : $baseNew;
        $dstFull = $dstDir . DIRECTORY_SEPARATOR . $finalNewName;

        if ($dstFull !== $realSrc) {
            if (!rename($realSrc, $dstFull)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No se pudo renombrar/mover el archivo.'
                ], 500);
            }
        }

        $realDst = realpath($dstFull);
        if ($realDst === false || strpos($realDst, $base) !== 0) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Ruta final fuera de la base permitida.'
            ], 400);
        }

        // Actualizar metadata
        $relativeOld = str_replace($base . DIRECTORY_SEPARATOR, '', $realSrc);
        $relativeOld = str_replace('\\', '/', $relativeOld);

        $relativeNew = str_replace($base . DIRECTORY_SEPARATOR, '', $realDst);
        $relativeNew = str_replace('\\', '/', $relativeNew);

        $metaIndex = $this->loadMetaIndex($base);
        $oldMeta = $metaIndex[$relativeOld] ?? [];

        unset($metaIndex[$relativeOld]);

        $metaIndex[$relativeNew] = [
            'name' => $meta['name'] ?? $oldMeta['name'] ?? $finalNewName,
            'title' => $meta['title'] ?? $oldMeta['title'] ?? $finalNewName,
            'version' => $meta['version'] ?? $oldMeta['version'] ?? '',
            'fInicio' => $meta['fInicio'] ?? $oldMeta['fInicio'] ?? '',
            'fFin' => $meta['fFin'] ?? $oldMeta['fFin'] ?? ''
        ];

        $this->saveMetaIndex($base, $metaIndex);

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Archivo actualizado correctamente.',
            'path' => $relativeNew,
            'name' => $metaIndex[$relativeNew]['name'],
            'title' => $metaIndex[$relativeNew]['title'],
            'version' => $metaIndex[$relativeNew]['version'],
            'fInicio' => $metaIndex[$relativeNew]['fInicio'] ?? '',
            'fFin' => $metaIndex[$relativeNew]['fFin'] ?? ''
        ]);
    }

    private function getMetaFilePath(string $base): string
    {
        return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.mpps_meta.json';
    }

    private function loadMetaIndex(string $base): array
    {
        $file = $this->getMetaFilePath($base);
        if (!file_exists($file)) {
            return [];
        }
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function saveMetaIndex(string $base, array $meta): void
    {
        $file = $this->getMetaFilePath($base);
        file_put_contents(
            $file,
            json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    public function getDepartamentos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $departamentos = $this->model->getDepartamentos();

        return $this->jsonResponse([
            'success' => true,
            'departamentos' => $departamentos
        ], 200);
    }

    private function seedDepartmentFolders(string $base, string $module): void
    {
        $module = $this->sanitizeSegment($module);
        if ($module === '')
            return;
        $moduleDir = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $module;
        if (!is_dir($moduleDir)) {
            @mkdir($moduleDir, 0777, true);
        }

        $result = $this->model->getDepartamentos();
        if (!is_array($result)) return;

        $rows = $result['data'] ?? null;
        if (!is_array($rows)) return;
        
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $nombre = isset($row['Nombre']) ? (string) $row['Nombre'] : '';
            $safeDept = $this->normalizeDept($nombre);  // ya quita caracteres raros, /, \, etc.
            if ($safeDept === '') continue;
            $deptDir = $moduleDir . DIRECTORY_SEPARATOR . $safeDept;
            if (!is_dir($deptDir)) {
                @mkdir($deptDir, 0777, true);
            }
        }
    }

}