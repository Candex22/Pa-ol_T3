<?php
session_start();
require_once('tool_management.php');

$conn = connectDB();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = isset($_POST["correo_electronico"]) ? $_POST["correo_electronico"] : "";
    $contrasena = isset($_POST["contrasena"]) ? $_POST["contrasena"] : "";

    // DEBUG: Log de entrada
    error_log("=== LOGIN ATTEMPT ===");
    error_log("Email: " . $correo);
    error_log("Password length: " . strlen($contrasena));

    // Validar campos vacíos
    if (empty($correo) || empty($contrasena)) {
        $_SESSION["login_error"] = "Todos los campos son obligatorios.";
        error_log("ERROR: Campos vacíos");
        header("Location: login.php");
        exit();
    }

    // Validar formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["login_error"] = "Formato de correo electrónico no válido.";
        error_log("ERROR: Email inválido");
        header("Location: login.php");
        exit();
    }

    // Consultar el usuario en la base de datos
    $stmt = $conn->prepare("SELECT id_user, contrasena, rol, estado, name_user FROM usuario WHERE correo_electronico = ?");
    if (!$stmt) {
        $_SESSION["login_error"] = "Error en la preparación: " . $conn->error;
        error_log("ERROR: Preparación SQL falló");
        header("Location: login.php");
        exit();
    }

    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    error_log("Rows found: " . $stmt->num_rows);

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $stored_password, $rol, $estado, $name_user);
        $stmt->fetch();

        // DEBUG: Log de datos encontrados
        error_log("User found - ID: " . $id_usuario);
        error_log("Stored password hash: " . substr($stored_password, 0, 30) . "...");
        error_log("Role: " . $rol);
        error_log("Status: " . $estado);
        error_log("Password hash length: " . strlen($stored_password));

        // VERIFICAR CONTRASEÑA
        $password_check = password_verify($contrasena, $stored_password);
        error_log("Password verify result: " . ($password_check ? "TRUE" : "FALSE"));

        if ($password_check) {
            error_log("Password verified successfully");
            
            // Verificar el estado del usuario
            if ($estado === 'pendiente') {
                $_SESSION["login_error"] = "Su cuenta está pendiente de aprobación por un administrador.";
                error_log("ERROR: Account pending");
                header("Location: login.php");
                exit();
            } elseif ($estado === 'inactivo') {
                $_SESSION["login_error"] = "Su cuenta ha sido desactivada. Contacte con un administrador.";
                error_log("ERROR: Account inactive");
                header("Location: login.php");
                exit();
            } elseif ($estado !== 'activo') {
                $_SESSION["login_error"] = "Estado de cuenta inválido: " . $estado;
                error_log("ERROR: Invalid status: " . $estado);
                header("Location: login.php");
                exit();
            }
            
            // Iniciar sesión
            $_SESSION["usuario_registrado"] = true;
            $_SESSION["id_usuario"] = $id_usuario;
            $_SESSION["correo_electronico"] = $correo;
            $_SESSION["rol"] = $rol;
            $_SESSION["name_user"] = $name_user;

            error_log("Session started successfully for user: " . $name_user);

            // Redirigir según el rol
            if ($rol === 'administrador') {
                error_log("Redirecting to admin panel");
                header("Location: admin_panel.php");
            } else {
                error_log("Redirecting to index");
                header("Location: ../index.php");
            }
            exit();
        } else {
            $_SESSION["login_error"] = "Contraseña incorrecta.";
            error_log("ERROR: Password verification failed");
            
            // DEBUG ADICIONAL: Verificar si es un problema de hash
            error_log("Testing direct comparison: " . ($contrasena === $stored_password ? "MATCH" : "NO MATCH"));
            
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION["login_error"] = "No se encontró una cuenta con ese correo electrónico.";
        error_log("ERROR: No user found with email: " . $correo);
        header("Location: login.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/register.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../scripts/alertas.js"></script>
    <title>Sistema de Pañol - Iniciar Sesión</title>
</head>

<body>
    <div class="container">
        <div class="form-container">
        <?php if (isset($_SESSION["registro_exitoso"]) && $_SESSION["registro_exitoso"] === true): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registro exitoso!',
                        text: 'Su cuenta está pendiente de aprobación por un administrador.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Aceptar'
                    });
                });
            </script>
            <?php unset($_SESSION["registro_exitoso"]); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION["login_error"])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de inicio de sesión',
                        text: '<?php echo $_SESSION["login_error"]; ?>',
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Aceptar'
                    });
                });
            </script>
            <?php unset($_SESSION["login_error"]); ?>
        <?php endif; ?>
        
        <h2 class="form-title">Iniciar Sesión</h2>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="correo_electronico">Correo electrónico</label>
                <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" placeholder="Ingrese su correo electrónico" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="contrasena">Contraseña</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="Ingrese su contraseña" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
            </div>
        </form>
        <div class="form-footer">
            ¿No tiene una cuenta? <a href="./register.php">Registrarse</a>
        </div>
        </div>
    </div>
</body>
</html>