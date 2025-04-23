<?php
session_start();
require_once('check_session.php');
?>

// Incluir la conexión a la base de datos
require_once('../components/tool_management.php');

// Función para obtener todas las herramientas disponibles
function getHerramientas() {
    $conn = connectDB();
    $sql = "SELECT * FROM herramientas WHERE cantidad > 0 ORDER BY nombre";
    $result = $conn->query($sql);
    $herramientas = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $herramientas[] = $row;
        }
    }
    
    $conn->close();
    return $herramientas;
}

// Función para crear un nuevo pedido
function crearPedido($curso, $retirante, $encargado, $items) {
    $conn = connectDB();
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // Insertar el pedido
        $stmt = $conn->prepare("INSERT INTO pedidos (curso, retirante, encargado) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $curso, $retirante, $encargado);
        $stmt->execute();
        
        $id_pedido = $conn->insert_id;
        
        // Insertar los detalles del pedido y actualizar el inventario
        foreach ($items as $item) {
            $herramienta_id = $item['herramienta'];
            $cantidad = $item['cantidad'];
            
            // Verificar stock disponible
            $stmt = $conn->prepare("SELECT cantidad FROM herramientas WHERE codigo = ?");
            $stmt->bind_param("i", $herramienta_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stock = $result->fetch_assoc()['cantidad'];
            
            if ($stock < $cantidad) {
                throw new Exception("Stock insuficiente para la herramienta con código: " . $herramienta_id);
            }
            
            // Insertar detalle
            $stmt = $conn->prepare("INSERT INTO detalle_pedido (id_pedido, herramienta, cantidad) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id_pedido, $herramienta_id, $cantidad);
            $stmt->execute();
            
            // Actualizar inventario
            $nueva_cantidad = $stock - $cantidad;
            $stmt = $conn->prepare("UPDATE herramientas SET cantidad = ? WHERE codigo = ?");
            $stmt->bind_param("ii", $nueva_cantidad, $herramienta_id);
            $stmt->execute();
        }
        
        // Confirmar transacción
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $conn->rollback();
        return $e->getMessage();
    }
    
    $conn->close();
}

// Variables para el formulario
$herramientas = getHerramientas();
$mensaje = '';
$tipo_mensaje = '';

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos
    $curso = $_POST['curso'];
    $retirante = $_POST['retirante'];
    $encargado = $_SESSION['usuario_id']; // ID del usuario logueado
    
    // Validar que hay al menos un ítem en el pedido
    if (!isset($_POST['items']) || count($_POST['items']) === 0) {
        $mensaje = "Debe seleccionar al menos una herramienta para el pedido.";
        $tipo_mensaje = "danger";
    } else {
        $items = [];
        
        // Procesar cada ítem del pedido
        foreach ($_POST['items'] as $index => $item) {
            $herramienta = $item['herramienta'];
            $cantidad = $item['cantidad'];
            
            // Validar cantidad
            if ($cantidad <= 0) {
                $mensaje = "La cantidad debe ser mayor a 0.";
                $tipo_mensaje = "danger";
                break;
            }
            
            $items[] = [
                'herramienta' => $herramienta,
                'cantidad' => $cantidad
            ];
        }
        
        // Si no hay errores, crear el pedido
        if (empty($mensaje)) {
            $resultado = crearPedido($curso, $retirante, $encargado, $items);
            
            if ($resultado === true) {
                // Redireccionar a la lista de pedidos
                header("Location: pedidos_lista.php?success=1");
                exit();
            } else {
                $mensaje = "Error al crear el pedido: " . $resultado;
                $tipo_mensaje = "danger";
            }
        }
    }
}

$logged_in = true; // El usuario está autenticado en este punto
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Pedido - Sistema de Pañol</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/style.css">
    <style>
        .item-herramienta {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Nuevo Pedido</h1>
            <a href="pedidos_lista.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a la lista
            </a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form id="pedidoForm" method="POST" action="">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="curso" class="form-label">Curso</label>
                                <select class="form-select" id="curso" name="curso" required>
                                    <option value="">Seleccione un curso</option>
                                    <option value="primero">1° Año</option>
                                    <option value="segundo">2° Año</option>
                                    <option value="tercero">3° Año</option>
                                    <option value="cuarto">4° Año</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="retirante" class="form-label">Retirante</label>
                                <input type="text" class="form-control" id="retirante" name="retirante" 
                                       placeholder="Nombre del alumno o profesor" required>
                            </div>
                        </div>
                    </div>

                    <h4 class="mb-3">Herramientas</h4>
                    <div id="itemsContainer">
                        <!-- Aquí se agregarán dinámicamente los ítems del pedido -->
                        <div class="item-herramienta" id="item-0">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="mb-3">
                                        <label class="form-label">Herramienta</label>
                                        <select class="form-select herramienta-select" name="items[0][herramienta]" required>
                                            <option value="">Seleccione una herramienta</option>
                                            <?php foreach ($herramientas as $herramienta): ?>
                                                <option value="<?php echo $herramienta['codigo']; ?>" 
                                                        data-stock="<?php echo $herramienta['cantidad']; ?>"
                                                        data-imagen="<?php echo htmlspecialchars($herramienta['imagen']); ?>">
                                                    <?php echo htmlspecialchars($herramienta['nombre']); ?> 
                                                    (Disponibles: <?php echo $herramienta['cantidad']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Cantidad</label>
                                        <input type="number" class="form-control cantidad-input" 
                                               name="items[0][cantidad]" min="1" value="1" required>
                                        <small class="stock-info text-muted"></small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Vista previa</label>
                                        <div class="preview-container text-center">
                                            <i class="fas fa-tools fa-2x text-secondary"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="mb-3 d-flex align-items-end h-100">
                                        <button type="button" class="btn btn-danger removeItem" style="display: none;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center mb-4">
                        <button type="button" id="addItemBtn" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> Agregar otra herramienta
                        </button>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="pedidos_lista.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Sistema de Gestión de Pañol</h5>
                    <p>Una solución completa para la administración de herramientas y préstamos en instituciones educativas.</p>
                </div>
                <div class="col-md-3">
                    <h5>Enlaces rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="../index.php" class="text-white">Inicio</a></li>
                        <li><a href="herramientas_lista.php" class="text-white">Herramientas</a></li>
                        <li><a href="pedidos_lista.php" class="text-white">Pedidos</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contacto</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> soporte@panol.com</li>
                        <li><i class="fas fa-phone me-2"></i> (123) 456-7890</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Sistema de Gestión de Pañol. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let itemCounter = 0;
            
            // Función para actualizar la vista previa y validación de cantidad
            function updatePreviewAndValidation(itemElement) {
                const herramientaSelect = itemElement.querySelector('.herramienta-select');
                const cantidadInput = itemElement.querySelector('.cantidad-input');
                const previewContainer = itemElement.querySelector('.preview-container');
                const stockInfo = itemElement.querySelector('.stock-info');
                
                if (herramientaSelect.selectedIndex > 0) {
                    const selectedOption = herramientaSelect.options[herramientaSelect.selectedIndex];
                    const stock = parseInt(selectedOption.dataset.stock);
                    const imagen = selectedOption.dataset.imagen;
                    
                    // Actualizar vista previa
                    if (imagen) {
                        previewContainer.innerHTML = `<img src="../${imagen}" alt="Vista previa" class="img-fluid" style="max-height: 50px;">`;
                    } else {
                        previewContainer.innerHTML = `<i class="fas fa-tools fa-2x text-secondary"></i>`;
                    }
                    
                    // Actualizar validación de cantidad
                    cantidadInput.max = stock;
                    stockInfo.textContent = `Máximo disponible: ${stock}`;
                    
                    if (parseInt(cantidadInput.value) > stock) {
                        cantidadInput.value = stock;
                    }
                } else {
                    previewContainer.innerHTML = `<i class="fas fa-tools fa-2x text-secondary"></i>`;
                    stockInfo.textContent = '';
                }
            }
            
            // Agregar evento a la primera herramienta
            const firstItem = document.getElementById('item-0');
            updatePreviewAndValidation(firstItem);
            
            firstItem.querySelector('.herramienta-select').addEventListener('change', function() {
                updatePreviewAndValidation(firstItem);
            });
            
            // Botón para agregar más herramientas
            document.getElementById('addItemBtn').addEventListener('click', function() {
                itemCounter++;
                
                // Verificar si aún hay herramientas disponibles para seleccionar
                if (document.querySelectorAll('.item-herramienta').length >= <?php echo count($herramientas); ?>) {
                    alert('Ya has agregado todas las herramientas disponibles.');
                    return;
                }
                
                const newItem = document.createElement('div');
                newItem.className = 'item-herramienta';
                newItem.id = `item-${itemCounter}`;
                
                newItem.innerHTML = `
                    <div class="row">
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label class="form-label">Herramienta</label>
                                <select class="form-select herramienta-select" name="items[${itemCounter}][herramienta]" required>
                                    <option value="">Seleccione una herramienta</option>
                                    <?php foreach ($herramientas as $herramienta): ?>
                                        <option value="<?php echo $herramienta['codigo']; ?>" 
                                                data-stock="<?php echo $herramienta['cantidad']; ?>"
                                                data-imagen="<?php echo htmlspecialchars($herramienta['imagen']); ?>">
                                            <?php echo htmlspecialchars($herramienta['nombre']); ?> 
                                            (Disponibles: <?php echo $herramienta['cantidad']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Cantidad</label>
                                <input type="number" class="form-control cantidad-input" 
                                       name="items[${itemCounter}][cantidad]" min="1" value="1" required>
                                <small class="stock-info text-muted"></small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div<div class="mb-3">
                                <label class="form-label">Vista previa</label>
                                <div class="preview-container text-center">
                                    <i class="fas fa-tools fa-2x text-secondary"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="mb-3 d-flex align-items-end h-100">
                                <button type="button" class="btn btn-danger removeItem">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('itemsContainer').appendChild(newItem);
                
                // Agregar eventos al nuevo ítem
                const herramientaSelect = newItem.querySelector('.herramienta-select');
                herramientaSelect.addEventListener('change', function() {
                    updatePreviewAndValidation(newItem);
                });
                
                newItem.querySelector('.removeItem').addEventListener('click', function() {
                    newItem.remove();
                    
                    // Mostrar el botón eliminar si hay más de un ítem
                    if (document.querySelectorAll('.item-herramienta').length > 1) {
                        document.querySelector('.removeItem').style.display = 'block';
                    } else {
                        document.querySelector('.removeItem').style.display = 'none';
                    }
                });
                
                // Mostrar el botón eliminar en todos los ítems si hay más de uno
                if (document.querySelectorAll('.item-herramienta').length > 1) {
                    document.querySelectorAll('.removeItem').forEach(btn => {
                        btn.style.display = 'block';
                    });
                }
            });
            
            // Validación del formulario antes de enviar
            document.getElementById('pedidoForm').addEventListener('submit', function(e) {
                let herramientasSeleccionadas = new Set();
                let error = false;
                
                // Verificar que no haya herramientas duplicadas
                document.querySelectorAll('.herramienta-select').forEach(select => {
                    if (select.value) {
                        if (herramientasSeleccionadas.has(select.value)) {
                            alert('No puede seleccionar la misma herramienta más de una vez. Por favor, ajuste la cantidad en lugar de agregar otra línea.');
                            error = true;
                        } else {
                            herramientasSeleccionadas.add(select.value);
                        }
                    }
                });
                
                // Verificar que las cantidades sean válidas
                document.querySelectorAll('.cantidad-input').forEach(input => {
                    const cantidad = parseInt(input.value);
                    const max = parseInt(input.max);
                    
                    if (cantidad <= 0) {
                        alert('La cantidad debe ser mayor a 0.');
                        error = true;
                    } else if (cantidad > max) {
                        alert(`La cantidad no puede ser mayor a ${max}.`);
                        error = true;
                    }
                });
                
                if (error) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>