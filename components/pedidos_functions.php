<?php
// Función para obtener todos los pedidos
function getPedidos() {
    $conn = connectDB();
    $pedidos = [];
    
    try {
        // Preparar la consulta principal
        $stmt = $conn->prepare(
            "SELECT p.id_pedido, p.curso, p.retirante, p.fecha_pedido, p.estado, 
                    p.fecha_devolucion, p.observaciones, 
                    u.nombre as nombre_encargado, u.apellido as apellido_encargado 
             FROM pedidos p 
             INNER JOIN usuario u ON p.encargado = u.id_user 
             ORDER BY p.fecha_pedido DESC"
        );
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta principal: " . $conn->error);
        }
        
        // Ejecutar la consulta principal
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta principal: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        // Preparar la consulta de detalles una sola vez
        $stmtDetalles = $conn->prepare(
            "SELECT d.id_detalle, d.cantidad, h.codigo, h.nombre, h.imagen 
             FROM detalle_pedido d 
             INNER JOIN herramientas h ON d.herramienta = h.codigo 
             WHERE d.id_pedido = ?"
        );
        
        if (!$stmtDetalles) {
            throw new Exception("Error al preparar la consulta de detalles: " . $conn->error);
        }
        
        // Procesar cada pedido
        while ($row = $result->fetch_assoc()) {
            $pedidoId = $row['id_pedido'];
            
            // Obtener detalles del pedido
            if (!$stmtDetalles->bind_param("i", $pedidoId)) {
                throw new Exception("Error al vincular parámetro: " . $stmtDetalles->error);
            }
            
            if (!$stmtDetalles->execute()) {
                throw new Exception("Error al ejecutar consulta de detalles: " . $stmtDetalles->error);
            }
            
            $resultDetalles = $stmtDetalles->get_result();
            $detalles = [];
            
            while ($detalle = $resultDetalles->fetch_assoc()) {
                $detalles[] = $detalle;
            }
            
            $row['detalles'] = $detalles;
            $pedidos[] = $row;
        }
        
        // Cerrar statements
        $stmtDetalles->close();
        $stmt->close();
        
    } catch (Exception $e) {
        // Manejar cualquier error
        error_log("Error en getPedidos: " . $e->getMessage());
    } finally {
        // Asegurarse de cerrar la conexión
        if ($conn) {
            $conn->close();
        }
    }
    
    return $pedidos;
}

// Definir colores según el estado del pedido
function getEstadoClass($estado) {
    switch ($estado) {
        case 'prestado':
            return 'bg-warning text-dark';
        case 'devuelto':
            return 'bg-success text-white';
        default:
            return 'bg-secondary text-white';
    }
}

// Obtener texto para mostrar según el curso
function getCursoText($curso) {
    switch ($curso) {
        case 'primero':
            return '1° Año';
        case 'segundo':
            return '2° Año';
        case 'tercero':
            return '3° Año';
        case 'cuarto':
            return '4° Año';
        default:
            return $curso;
    }
}
?>
