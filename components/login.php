<?php
session_start(); // Iniciar sesión al comienzo del archivo

require_once('tool_management.php');

$conn = connectDB();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = isset($_POST["correo_electronico"]) ? $_POST["correo_electronico"] : "";
    $contrasena = isset($_POST["contrasena"]) ? $_POST["contrasena"] : "";

    // Validar campos vacíos
    if (empty($correo) || empty($contrasena)) {
        $_SESSION["login_error"] = "Todos los campos son obligatorios.";
        header("Location: login.php");
        exit();
    }

    // Validar formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $_SESSION["login_error"] = "Formato de correo electrónico no válido.";
        header("Location: login.php");
        exit();
    }

    // Consultar el usuario en la base de datos
    $stmt = $conn->prepare("SELECT id_user, contrasena FROM usuario WHERE correo_electronico = ?");
    if (!$stmt) {
        $_SESSION["login_error"] = "Error en la preparación: " . $conn->error;
        header("Location: login.php");
        exit();
    }

    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $stored_password);
        $stmt->fetch();

        // Verificar la contraseña ingresada con la almacenada
        if ($contrasena === $stored_password) {
            // Iniciar sesión
            $_SESSION["usuario_registrado"] = true; // Indicar que el usuario está autenticado
            $_SESSION["id_usuario"] = $id_usuario;
            $_SESSION["correo_electronico"] = $correo;

            // Redirigir al usuario a la página principal
            header("Location: ../index.php");
            exit();
        } else {
            $_SESSION["login_error"] = "Contraseña incorrecta.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION["login_error"] = "No se encontró una cuenta con ese correo electrónico.";
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
                        text: 'Por favor, inicie sesión con sus credenciales.',
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