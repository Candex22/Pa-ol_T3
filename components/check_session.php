<?php
if (!isset($_SESSION['usuario_registrado']) || $_SESSION['usuario_registrado'] !== true) {
    // Si no hay sesión activa, redirigir al login
    header("Location: login.php");
    exit();
}
?>
