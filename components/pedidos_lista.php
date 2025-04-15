<?php
session_start();

// Verificar si el usuario está registrado
if (!isset($_SESSION['usuario_registrado']) || $_SESSION['usuario_registrado'] !== true) {
    header("Location: login.php");
    exit();
}

// Incluir la conexión a la base de datos
require_once('../components/tool_management.php');

// Función para obtener todos los pedidos
function getPedidos() {
    $conn = connectDB();
    
    $sql = "SELECT p.id_pedido, p.curso, p.retirante, p.fecha_pedido, p.estado, p.fecha_devolucion, 
                   p.observaciones, u.nombre as nombre_encargado, u.apellido as apellido_encargado
            FROM pedidos p
            INNER JOIN usuario u ON p.encargado = u.id_user
            ORDER BY p.fecha_pedido DESC";
    
    $result = $conn->query($sql);
    $pedidos = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Obtener los detalles (herramientas) de cada pedido
            $pedidoId = $row['id_pedido'];
            $sqlDetalles = "SELECT d.id_detalle, d.cantidad, h.codigo, h.nombre, h.imagen
                           FROM detalle_pedido d
                           INNER JOIN herramientas h ON d.herramienta = h.codigo
                           WHERE d.id_pedido = $pedidoId";
            
            $resultDetalles = $conn->query($sqlDetalles);
            $detalles = [];
            
            if ($resultDetalles->num_rows > 0) {
                while($detalle = $resultDetalles->fetch_assoc()) {
                    $detalles[] = $detalle;
                }
            }
            
            $row['detalles'] = $detalles;
            $pedidos[] = $row;
        }
    }
    
    $conn->close();
    return $pedidos;
}

// Obtener todos los pedidos
$pedidos = getPedidos();

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

// Obtener texto para mostrar según el curso
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

$logged_in = true; // El usuario está autenticado en este punto
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Sistema de Pañol</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>
    <?php include('menu.php'); ?>

    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestión de Pedidos</h1>
            <a href="pedido_form.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Nuevo Pedido
            </a>
        </div>

        <?php if (empty($pedidos)): ?>
            <div class="alert alert-info">
                No hay pedidos registrados en el sistema.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Curso</th>
                            <th>Retirante</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Herramientas</th>
                            <th>Encargado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?php echo $pedido['id_pedido']; ?></td>
                                <td><?php echo getCursoText($pedido['curso']); ?></td>
                                <td><?php echo htmlspecialchars($pedido['retirante']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                <td>
                                    <span class="badge <?php echo getEstadoClass($pedido['estado']); ?>">
                                        <?php echo ucfirst($pedido['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#detalles-<?php echo $pedido['id_pedido']; ?>">
                                        Ver Herramientas (<?php echo count($pedido['detalles']); ?>)
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars($pedido['nombre_encargado'] . ' ' . $pedido['apellido_encargado']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="pedido_detalle.php?id=<?php echo $pedido['id_pedido']; ?>" class="btn btn-sm btn-info" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($pedido['estado'] !== 'devuelto'): ?>
                                            <a href="pedido_devolucion.php?id=<?php echo $pedido['id_pedido']; ?>" class="btn btn-sm btn-success" title="Registrar Devolución">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="detalles-<?php echo $pedido['id_pedido']; ?>">
                                <td colspan="8">
                                    <div class="card card-body bg-light">
                                        <h6>Herramientas del Pedido:</h6>
                                        <div class="row">
                                            <?php foreach ($pedido['detalles'] as $detalle): ?>
                                                <div class="col-md-3 mb-2">
                                                    <div class="card">
                                                        <div class="card-body d-flex align-items-center">
                                                            <?php if (!empty($detalle['imagen'])): ?>
                                                                <img src="<?php echo '../' . htmlspecialchars($detalle['imagen']); ?>" alt="<?php echo htmlspecialchars($detalle['nombre']); ?>" class="tool-image me-2">
                                                            <?php else: ?>
                                                                <i class="fas fa-tools me-2 text-secondary"></i>
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($detalle['nombre']); ?></strong>
                                                                <br>
                                                                <small>Cantidad: <?php echo $detalle['cantidad']; ?></small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if ($pedido['estado'] === 'devuelto'): ?>
                                            <div class="mt-3">
                                                <h6>Fecha de devolución:</h6>
                                                <p><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_devolucion'])); ?></p>
                                                
                                                <?php if (!empty($pedido['observaciones'])): ?>
                                                    <h6>Observaciones:</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($pedido['observaciones'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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