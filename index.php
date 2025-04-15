<?php

session_start(); 

//hola
if (!isset($_SESSION['usuario_registrado']) || $_SESSION['usuario_registrado'] !== true) {
    
    header("Location: components/register.php");
    exit();
}
// Include the tool management module
require_once('components/tool_management.php');

// Get tool count
$conn = connectDB();
$stats = [];

// Total number of tools
$result = $conn->query("SELECT COUNT(*) as total FROM herramientas");
$stats['total_tools'] = $result->fetch_assoc()['total'];

// Total quantity of all tools
$result = $conn->query("SELECT SUM(cantidad) as total_quantity FROM herramientas");
$stats['total_quantity'] = $result->fetch_assoc()['total_quantity'] ?: 0;

// Tools with low stock (less than 5)
$result = $conn->query("SELECT COUNT(*) as low_stock FROM herramientas WHERE cantidad < 5");
$stats['low_stock'] = $result->fetch_assoc()['low_stock'];

// Total number of orders
$result = $conn->query("SELECT COUNT(*) as total FROM pedidos");
$stats['total_orders'] = $result->fetch_assoc()['total'];

// Recent tools (last 5 added)
$recent_tools = [];
$result = $conn->query("SELECT * FROM herramientas ORDER BY codigo DESC LIMIT 5");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $recent_tools[] = $row;
    }
}

$conn->close();

// Check if user is logged in (you should implement proper authentication)
$logged_in = true; // Replace with actual authentication check
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Pañol</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <?php include('components/menu.php'); ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4">Sistema de Gestión de Pañol</h1>
                    <p class="lead">Administra eficientemente el inventario de herramientas y los préstamos a diferentes cursos.</p>
                </div>
                <div class="col-lg-6">
                    <img src="https://via.placeholder.com/600x400" alt="Pañol" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="container mb-5">
        <h2 class="text-center mb-4">Resumen del Sistema</h2>
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white">
                    <i class="fas fa-tools stat-icon"></i>
                    <div class="stat-number"><?php echo $stats['total_tools']; ?></div>
                    <div class="stat-label">Herramientas Registradas</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white">
                    <i class="fas fa-boxes stat-icon"></i>
                    <div class="stat-number"><?php echo $stats['total_quantity']; ?></div>
                    <div class="stat-label">Unidades en Inventario</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-dark">
                    <i class="fas fa-exclamation-triangle stat-icon"></i>
                    <div class="stat-number"><?php echo $stats['low_stock']; ?></div>
                    <div class="stat-label">Herramientas con Bajo Stock</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white">
                    <i class="fas fa-clipboard-list stat-icon"></i>
                    <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                    <div class="stat-label">Pedidos Registrados</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Menu Section -->
    <section class="container mb-5">
        <h2 class="text-center mb-4">Accesos Rápidos</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card menu-card">
                    <div class="card-body text-center">
                        <i class="fas fa-tools menu-icon text-primary"></i>
                        <h3>Gestión de Herramientas</h3>
                        <p>Administra el inventario de herramientas, agrega nuevas o actualiza las existentes.</p>
                        <a href="components/herramientas_lista.php" class="btn btn-primary">Acceder</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card menu-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clipboard-list menu-icon text-success"></i>
                        <h3>Gestión de Pedidos</h3>
                        <p>Registra nuevos pedidos y gestiona el estado de las herramientas prestadas.</p>
                        <a href="components/pedidos_lista.php" class="btn btn-success">Acceder</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card menu-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar menu-icon text-info"></i>
                        <h3>Reportes y Estadísticas</h3>
                        <p>Visualiza informes detallados sobre el uso de herramientas y pedidos.</p>
                        <a href="components/reportes.php" class="btn btn-info">Acceder</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Tools Section -->
    <section class="container mb-5">
        <div class="card">
            <div class="card-header bg-light">
                <h3 class="mb-0">Herramientas Recientes</h3>
            </div>
            <div class="card-body">
                <?php if (empty($recent_tools)): ?>
                    <p class="text-muted">No hay herramientas registradas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Cantidad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_tools as $tool): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($tool['imagen'])): ?>
                                                <img src="<?php echo htmlspecialchars($tool['imagen']); ?>" alt="<?php echo htmlspecialchars($tool['nombre']); ?>" class="tool-image">
                                            <?php else: ?>
                                                <i class="fas fa-tools text-muted"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($tool['codigo']); ?></td>
                                        <td><?php echo htmlspecialchars($tool['nombre']); ?></td>
                                        <td>
                                            <?php if ($tool['cantidad'] < 5): ?>
                                                <span class="badge bg-warning text-dark"><?php echo $tool['cantidad']; ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?php echo $tool['cantidad']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="components/herramienta_form.php?codigo=<?php echo $tool['codigo']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="components/herramientas_lista.php" class="btn btn-outline-primary">Ver todas las herramientas</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Sistema de Gestión de Pañol</h5>
                    <p>Una solución completa para la administración de herramientas y préstamos en instituciones educativas.</p>
                </div>
                <div class="col-md-3">
                    <h5>Enlaces rápidos</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Inicio</a></li>
                        <li><a href="components/herramientas_lista.php" class="text-white">Herramientas</a></li>
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