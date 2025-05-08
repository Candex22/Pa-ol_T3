<?php
$logged_in = isset($_SESSION['usuario_registrado']) && $_SESSION['usuario_registrado'] === true;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/Pa-ol_T3/index.php">
            <i class="fas fa-tools me-2"></i>
            Sistema de Pañol
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="/Pa-ol_T3/index.php">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Pa-ol_T3/components/herramientas_lista.php">Herramientas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/Pa-ol_T3/components/pedidos_lista.php">Pedidos</a>
                </li>
            </ul>
            <div class="navbar-nav">
                <?php if ($logged_in): ?>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#perfilModal">
                        <i class="fas fa-user me-1"></i> Mi Perfil
                    </a>
                    <a class="nav-link" href="/Pa-ol_T3/components/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="/Pa-ol_T3/components/login.php">
                        <i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Modal de Perfil -->
<?php if ($logged_in): 
    // Obtener información del usuario desde la base de datos
    require_once(__DIR__ . '/tool_management.php');
    $conn = connectDB();
    $correo = $_SESSION["correo_electronico"];
    $usuario = null;
    
    if ($stmt = $conn->prepare("SELECT id_user, nombre, apellido, correo_electronico, name_user FROM usuario WHERE correo_electronico = ? LIMIT 1")) {
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
        }
        $stmt->close();
    }
    $conn->close();
    
    // Determinar el rol del usuario (como no hay un campo específico, lo asignamos como "Administrador")
    $rol = "Administrador";
?>
<div class="modal fade" id="perfilModal" tabindex="-1" aria-labelledby="perfilModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="perfilModalLabel">Mi Perfil</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($usuario): ?>
                    <div class="text-center mb-4">
                        <div class="avatar-circle mx-auto mb-3">
                            <span class="initials"><?php echo substr($usuario['nombre'], 0, 1) . substr($usuario['apellido'], 0, 1); ?></span>
                        </div>
                        <h4><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></h4>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($rol); ?></span>
                    </div>
                    <div class="user-info">
                        <p><strong>Nombre de usuario:</strong> <?php echo htmlspecialchars($usuario['name_user']); ?></p>
                        <p><strong>Correo electrónico:</strong> <?php echo htmlspecialchars($usuario['correo_electronico']); ?></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        No se pudo cargar la información del usuario.
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

