<?php
session_start();
?>

<?php
// Incluir la conexión a la base de datos
require_once('../components/tool_management.php');

// Verificar si se proporcionó un ID de pedido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pedidos_lista.php");
    exit();
}

$id_pedido = $_GET['id'];

// Función para obtener los detalles del pedido
function getPedidoDetalles($id_pedido) {
    $conn = connectDB();
    
    $sql = "SELECT p.id_pedido, p.curso, p.retirante, p.fecha_pedido, p.estado, p.fecha_devolucion, 
                   p.observaciones, u.nombre as nombre_encargado, u.apellido as apellido_encargado
            FROM pedidos p
            INNER JOIN usuario u ON p.encargado = u.id_user
            WHERE p.id_pedido = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->close();
        return null;
    }
    
    $pedido = $result->fetch_assoc();
    
    // Obtener los detalles (herramientas) del pedido
    $sqlDetalles = "SELECT d.id_detalle, d.cantidad, h.codigo, h.nombre, h.imagen
                   FROM detalle_pedido d
                   INNER JOIN herramientas h ON d.herramienta = h.codigo
                   WHERE d.id_pedido = ?";
    
    $stmt = $conn->prepare($sqlDetalles);
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $resultDetalles = $stmt->get_result();
    $detalles = [];
    
    if ($resultDetalles->num_rows > 0) {
        while($detalle = $resultDetalles->fetch_assoc()) {
            $detalles[] = $detalle;
        }
    }
    
    $pedido['detalles'] = $detalles;
    
    $conn->close();
    return $pedido;
}

// Obtener el pedido
$pedido = getPedidoDetalles($id_pedido);

if (!$pedido) {
    // Si no se encuentra el pedido, redirigir
    header("Location: pedidos_lista.php");
    exit();
}

// Función para obtener el texto del curso
function getCursoText($curso) {
    switch ($curso) {
        case 'primero':
            return '1° Año';
        case 'segundo':
            return '2° Año';
        case 'tercero':
            return '3° Año';
        case 'cuarto':
            return '4° Año';
        default:
            return $curso;
    }
}

// Definir colores según el estado del pedido
function getEstadoClass($estado) {
    switch ($estado) {
        case 'prestado':
            return 'bg-warning text-dark';
        case 'devuelto':
            return 'bg-success text-white';
        default:
            return 'bg-secondary text-white';
    }
}

$logged_in = true; // El usuario está autenticado en este punto
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Pedido #<?php echo $id_pedido; ?> - Sistema de Pañol</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Detalle de Pedido #<?php echo $id_pedido; ?></h1>
            <div>
                <?php if ($pedido['estado'] !== 'devuelto'): ?>
                    <a href="pedido_devolucion.php?id=<?php echo $id_pedido; ?>" class="btn btn-success me-2">
                        <i class="fas fa-check-circle"></i> Registrar Devolución
                    </a>
                <?php endif; ?>
                <a href="pedidos_lista.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a la lista
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        Información del Pedido
                    </div>
                    <div class="card-body">
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Estado:</label>
                            <div class="col-sm-8">
                                <span class="badge <?php echo getEstadoClass($pedido['estado']); ?>">
                                    <?php echo ucfirst($pedido['estado']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Fecha:</label>
                            <div class="col-sm-8">
                                <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Curso:</label>
                            <div class="col-sm-8">
                                <?php echo getCursoText($pedido['curso']); ?>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Retirante:</label>
                            <div class="col-sm-8">
                                <?php echo htmlspecialchars($pedido['retirante']); ?>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-sm-4 col-form-label fw-bold">Encargado:</label>
                            <div class="col-sm-8">
                                <?php echo htmlspecialchars($pedido['nombre_encargado'] . ' ' . $pedido['apellido_encargado']); ?>
                            </div>
                        </div>
                        <?php if ($pedido['estado'] === 'devuelto'): ?>
                            <div class="mb-3 row">
                                <label class="col-sm-4 col-form-label fw-bold">Fecha de devolución:</label>
                                <div class="col-sm-8">
                                    <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_devolucion'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($pedido['estado'] === 'devuelto' && !empty($pedido['observaciones'])): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            Observaciones de la Devolución
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($pedido['observaciones'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Herramientas del Pedido
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Imagen</th>
                                        <th>Herramienta</th>
                                        <th>Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedido['detalles'] as $detalle): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($detalle['imagen'])): ?>
                                                    <img src="<?php echo '../' . htmlspecialchars($detalle['imagen']); ?>" alt="<?php echo htmlspecialchars($detalle['nombre']); ?>" class="tool-image">
                                                <?php else: ?>
                                                    <i class="fas fa-tools text-secondary"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($detalle['nombre']); ?></td>
                                            <td><?php echo $detalle['cantidad']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
</body>
</html>