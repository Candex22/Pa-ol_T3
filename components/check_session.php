<?php
if (!isset($_SESSION['usuario_registrado']) || $_SESSION['usuario_registrado'] !== true) {
    // Si no hay sesión activa, redirigir al login
    header("Location: login.php");
    exit();
}

// Verificar si el usuario está activo
if (!isset($_SESSION['rol']) || !isset($_SESSION['estado']) || $_SESSION['estado'] !== 'activo') {
    // Destruir la sesión e informar al usuario
    session_unset();
    session_destroy();
    session_start(); // Iniciar sesión para poder mostrar mensajes
    $_SESSION["login_error"] = "Su cuenta no está activa. Contacte con un administrador.";
    header("Location: login.php");
    exit();
}
?>