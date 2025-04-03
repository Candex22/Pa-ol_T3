<?php
// Include the tool management module
require_once('tool_management.php');

// Process delete request
$message = '';
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    if (deleteTool($delete_id)) {
        $message = "Herramienta eliminada correctamente.";
    } else {
        $message = "Error al eliminar la herramienta. Puede estar asociada a pedidos existentes.";
    }
}

// Get all tools
$tools = getAllTools();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Herramientas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <style>
        .tool-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <?php include('menu.php'); ?>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestión de Herramientas</h1>
            <a href="herramienta_form.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Agregar Herramienta
            </a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($tools)): ?>
            <div class="alert alert-info">No hay herramientas registradas.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Imagen</th>
                            <th>Cantidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tools as $tool): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tool['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($tool['nombre']); ?></td>
                                <td>
                                    <?php if (!empty($tool['imagen'])): ?>
                                        <img src="<?php echo htmlspecialchars($tool['imagen']); ?>" alt="<?php echo htmlspecialchars($tool['nombre']); ?>" class="tool-image">
                                    <?php else: ?>
                                        <span class="text-muted">Sin imagen</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($tool['cantidad']); ?></td>
                                <td>
                                    <a href="herramienta_form.php?codigo=<?php echo $tool['codigo']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $tool['codigo']; ?>" data-name="<?php echo htmlspecialchars($tool['nombre']); ?>">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro que deseas eliminar la herramienta <span id="toolName"></span>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Setup delete confirmation modal
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                document.getElementById('toolName').textContent = name;
                document.getElementById('confirmDelete').href = 'herramientas_lista.php?delete=' + id;

                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>

</html>