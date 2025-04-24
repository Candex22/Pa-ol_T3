<?php
session_start(); // Iniciar la sesión al comienzo del archivo

// Verificar si el usuario ya está registrado
if (isset($_SESSION['usuario_registrado']) && $_SESSION['usuario_registrado'] === true) {
    header("Location: ../index.php"); // Redirigir al index si ya está registrado
    exit();
}

require_once('tool_management.php');

$conn = connectDB();

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = isset($_POST["nombre"]) ? $_POST["nombre"] : "";
    $apellido = isset($_POST["apellido"]) ? $_POST["apellido"] : "";
    $name_user = isset($_POST["nickname"]) ? $_POST["nickname"] : "";
    $correo = isset($_POST["correo_electronico"]) ? $_POST["correo_electronico"] : "";
    $contrasena = isset($_POST["contrasena"]) ? $_POST["contrasena"] : "";
    $confir_contrasena = isset($_POST["confir_contrasena"]) ? $_POST["confir_contrasena"] : "";

    // Validar campos vacíos
    if (empty($nombre) || empty($apellido) || empty($name_user) || empty($correo) || empty($contrasena)) {
        $_SESSION["register_error"] = "Todos los campos son obligatorios.";
        header("Location: register.php");
        exit();
    }

    // Validar formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["register_error"] = "Formato de correo electrónico no válido.";
        header("Location: register.php");
        exit();
    }

    // Validar que las contraseñas coincidan
    if ($contrasena !== $confir_contrasena) {
        $_SESSION["register_error"] = "Las contraseñas no coinciden.";
        header("Location: register.php");
        exit();
    }

    // Validar longitud de contraseña
    if (strlen($contrasena) <= 4) {
        $_SESSION["register_error"] = "La contraseña debe tener más de 4 caracteres.";
        header("Location: register.php");
        exit();
    }

    // Validar existencia previa en la base de datos
    $stmt_check = $conn->prepare("SELECT * FROM usuario WHERE correo_electronico = ? OR name_user = ? OR nombre = ? OR apellido = ? LIMIT 1");
    $stmt_check->bind_param("ssss", $correo, $name_user, $nombre, $apellido);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check && $result_check->num_rows > 0) {
        $usuario_existente = $result_check->fetch_assoc();
        if ($usuario_existente['correo_electronico'] === $correo) {
            $_SESSION["register_error"] = "El correo electrónico ya está registrado.";
        } elseif ($usuario_existente['name_user'] === $name_user) {
            $_SESSION["register_error"] = "El nombre de usuario ya está registrado.";
        } elseif ($usuario_existente['apellido'] === $apellido) {
            $_SESSION["register_error"] = "El apellido ya está registrado.";
        } else {
            $_SESSION["register_error"] = "Usuario ya existente.";
        }
        $stmt_check->close();
        header("Location: register.php");
        exit();
    }
    $stmt_check->close();

    // Prepare statement para evitar inyección SQL
    $stmt = $conn->prepare("INSERT INTO usuario (name_user, nombre, apellido, correo_electronico, contrasena) VALUES (?, ?, ?, ?, ?)");

    // Verifica si la consulta es válida
    if (!$stmt) {
        die("Error en la preparación: " . $conn->error);
    }

    // Bind de parámetros (todos son strings)
    $stmt->bind_param("sssss", $name_user, $nombre, $apellido, $correo, $contrasena);

    if ($stmt->execute()) {
        $_SESSION["registro_exitoso"] = true; // Variable de sesión para mostrar mensaje en login
        header("Location: login.php"); // Redirigir al login después del registro
        exit();
    } else {
        die("Error al registrar usuario: " . $stmt->error);
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Pañol - Registro</title>
    <link rel="stylesheet" href="../styles/register.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../scripts/alertas.js"></script>
</head>
<body>

    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Registro de Usuario</h2>
            
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" placeholder="Ingrese su nombre" name="nombre" id="nombre" maxlength="20" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]{1,20}" title="Solo letras, máximo 20 caracteres" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Apellido</label>
                    <input type="text" class="form-control" placeholder="Ingrese su apellido" name="apellido" id="apellido" maxlength="20" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ ]{1,20}" title="Solo letras, máximo 20 caracteres" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre de usuario</label>
                    <input type="text" class="form-control" placeholder="Elija un nombre de usuario" name="nickname">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" placeholder="Ingrese su correo electrónico" name="correo_electronico">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" class="form-control" placeholder="Cree una contraseña" name="contrasena">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirme contraseña</label>
                    <input type="password" class="form-control" placeholder="Confirme su contraseña" name="confir_contrasena">
                </div>
                
                <div class="form-actions">
                <button type="submit" class="btn btn-primary">Registrarse</button>
                <button type="button" class="btn btn-warning" id="btn-limpiar">Limpiar</button>
                    
                </div>
                
                <div class="form-footer">
                    ¿Ya tiene una cuenta? <a href="./login.php">Iniciar sesión</a>
                </div>
                <?php
                if (isset($_SESSION["registro_exitoso"]) && $_SESSION["registro_exitoso"] == true) {
                    echo '<div class="registro-exitoso">';

                    echo '</div>';
                    unset($_SESSION["registro_exitoso"]); // Eliminar la variable para que no se muestre siempre
                }
    ?>

            </form>
        </div>
    <?php if (isset($_SESSION["register_error"])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de registro',
                    text: '<?php echo $_SESSION["register_error"]; ?>',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Aceptar'
                });
            });
        </script>
        <?php unset($_SESSION["register_error"]); ?>
    <?php endif; ?>
    </div>
</body>
</html>