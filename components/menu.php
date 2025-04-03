<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/PA-OL_T3/index.php">
                <i class="fas fa-tools me-2"></i>
                Sistema de Pañol
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="/PA-OL_T3/index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="components/herramientas_lista.php">Herramientas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="components/pedidos_lista.php">Pedidos</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <?php if ($logged_in): ?>
                        <a class="nav-link" href="components/perfil.php">
                            <i class="fas fa-user me-1"></i> Mi Perfil
                        </a>
                        <a class="nav-link" href="components/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>