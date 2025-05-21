<?php
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    // Si no es administrador, redirigir a la página principal
    header("Location: ../index.php");
    exit();
}
?>