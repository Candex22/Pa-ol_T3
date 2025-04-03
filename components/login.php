<?php
session_start(); // Iniciar sesión al comienzo del archivo

require_once('tool_management.php');

$conn = connectDB();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = isset($_POST["correo_electronico"]) ? $_POST["correo_electronico"] : "";
    $contrasena = isset($_POST["contrasena"]) ? $_POST["contrasena"] : "";

    // Validar campos vacíos
    if (empty($correo) || empty($contrasena)) {
        die("Error: Todos los campos son obligatorios.");
    }

    // Validar formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        die("Error: Formato de correo electrónico no válido.");
    }

    // Consultar el usuario en la base de datos
    $stmt = $conn->prepare("SELECT id_user, contrasena FROM usuario WHERE correo_electronico = ?");
    if (!$stmt) {
        die("Error en la preparación: " . $conn->error);
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
            $_SESSION["id_usuario"] = $id_usuario;
            $_SESSION["correo_electronico"] = $correo;
            // Después de verificar la contraseña en login.php
            $_SESSION["id_usuario"] = $id_usuario;
            $_SESSION["name_user"] = $name_user; // Asegúrate de que $name_user contiene el nickname del usuario
            $_SESSION["correo_electronico"] = $correo;
            // Redirigir al usuario a la página principal
            header("Location: ../index.php");
            exit();
        } else {
            die("Error: Contraseña incorrecta.");
        }
    } else {
        die("Error: No se encontró una cuenta con ese correo electrónico.");
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
    <link type="text">
    <!--    <title>Sistema de Pañol - Iniciar Sesión</title> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">


</head>
<style>

body {
    background-color: #f5f5f5;
}


.container {
    max-width: 600px;
    margin: 40px auto;
    padding: 20px;
}

.form-container {
    background-color: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.form-title {
    font-size: 24px;
    margin-bottom: 25px;
    color: #333;
    text-align: center;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s;
}

.form-control:focus {
    border-color: #0d6efd;
    outline: none;
}

.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: background-color 0.3s;
}

.btn-primary {
    background-color: #0d6efd;
    color: white;
}

.btn-primary:hover {
    background-color: #0b5ed7;
}

.btn-warning {
    background-color: #ffc107;
    color: #333;
}

.btn-warning:hover {
    background-color: #ffca2c;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.form-footer {
    text-align: center;
    margin-top: 20px;
    color: #666;
}

.form-footer a {
    color: #0d6efd;
    text-decoration: none;
}

.form-footer a:hover {
    text-decoration: underline;
}
</style>
<body>
<?php include('menu.php'); ?>

<div class="container">
        <div class="form-container">
            <h2 class="form-title">Inicio de Usuario</h2>
            
            <form action="login.php" method="POST">

                <div class="form-group">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" placeholder="Ingrese su correo electrónico" name="correo_electronico">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" class="form-control" placeholder="Cree una contraseña" name="contrasena">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-warning">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Iniciarse</button>
                </div>
                
                <div class="form-footer">
                    ¿No tiene una cuenta? <a href="register.php">Registrarse</a>
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
    </div>
</body>

</html>