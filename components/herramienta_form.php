<?php
session_start();
// Include the tool management module
require_once('tool_management.php');

// Initialize variables
$tool = [
    'codigo' => '',
    'nombre' => '',
    'cantidad' => '',
    'imagen' => ''
];
$is_edit = false;
$message = '';

// Check if we're editing an existing tool
if (isset($_GET['codigo']) && !empty($_GET['codigo'])) {
    $tool_id = $_GET['codigo'];
    $tool = getTool($tool_id);
    $is_edit = true;
    
    if (!$tool) {
        $message = "La herramienta no fue encontrada.";
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $cantidad = $_POST['cantidad'];
    $imagen = null;
    
    // Check if an image was uploaded
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $imagen = uploadToolImage($_FILES['imagen']);
        if (!$imagen) {
            $message = "Error al subir la imagen. Verifica que sea una imagen válida (JPG, PNG, GIF) y menor a 5MB.";
        }
    } elseif ($is_edit) {
        // Keep existing image if editing and no new image
        $imagen = $tool['imagen'];
    }
    
    if (empty($message)) {
        if ($is_edit) {
            // Update existing tool
            if (editTool($tool['codigo'], $nombre, $cantidad, $imagen)) {
                $message = "Herramienta actualizada correctamente.";
                // Refresh tool data
                $tool = getTool($tool['codigo']);
            } else {
                $message = "Error al actualizar la herramienta.";
            }
        } else {
            // Add new tool
            $result = addTool($nombre, $cantidad, $imagen);
            if ($result) {
                $message = "Herramienta agregada correctamente con código: " . $result;
                // Clear form
                $tool = [
                    'codigo' => '',
                    'nombre' => '',
                    'cantidad' => '',
                    'imagen' => ''
                ];
            } else {
                $message = "Error al agregar la herramienta.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Editar' : 'Agregar'; ?> Herramienta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container mt-5">
        <h1><?php echo $is_edit ? 'Editar' : 'Agregar'; ?> Herramienta</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre de la herramienta</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($tool['nombre']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="cantidad" class="form-label">Cantidad disponible</label>
                <input type="number" class="form-control" id="cantidad" name="cantidad" value="<?php echo htmlspecialchars($tool['cantidad']); ?>" min="0" required>
            </div>
            
            <div class="mb-3">
                <label for="imagen" class="form-label">Imagen de la herramienta</label>
                <input type="file" class="form-control" id="imagen" name="imagen">
                <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB.</small>
                
                <?php if (!empty($tool['imagen'])): ?>
                    <div class="mt-2">
                        <p>Imagen actual:</p>
                        <img src="<?php echo htmlspecialchars($tool['imagen']); ?>" alt="Imagen de la herramienta" class="preview-image">
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Actualizar' : 'Agregar'; ?> Herramienta</button>
            <a href="herramientas_lista.php" class="btn btn-secondary">Volver a la lista</a>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview image before upload
        document.getElementById('imagen').onchange = function(evt) {
            const [file] = this.files;
            if (file) {
                // Remove any existing preview
                const existingPreview = document.querySelector('.preview-new');
                if (existingPreview) {
                    existingPreview.remove();
                }
                
                // Create preview
                const preview = document.createElement('div');
                preview.className = 'mt-2 preview-new';
                preview.innerHTML = '<p>Vista previa:</p><img src="' + URL.createObjectURL(file) + '" alt="Vista previa" class="preview-image">';
                
                this.parentNode.appendChild(preview);
            }
        };
    </script>
</body>
</html>