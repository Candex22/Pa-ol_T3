<?php
// Función para obtener todas las herramientas disponibles
function getHerramientas() {
    $conn = connectDB();
    
    try {
        $stmt = $conn->prepare("SELECT * FROM herramientas WHERE cantidad > 0 ORDER BY nombre");
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $herramientas = [];
        
        while($row = $result->fetch_assoc()) {
            $herramientas[] = $row;
        }
        
        $stmt->close();
        return $herramientas;
        
    } catch (Exception $e) {
        error_log("Error en getHerramientas: " . $e->getMessage());
        return [];
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
}

// Función para crear un nuevo pedido
function crearPedido($curso, $retirante, $encargado, $items) {
    if (empty($encargado)) {
        return ['success' => false, 'mensaje' => 'El ID del encargado es requerido'];
    }

    $conn = connectDB();
    $resultado = true;
    $mensaje = "";
    
    try {
        // Iniciar transacción
        $conn->begin_transaction();
        
        // Verificar que el encargado exista
        $check_stmt = $conn->prepare("SELECT id_user FROM usuario WHERE id_user = ?");
        if (!$check_stmt) {
            throw new Exception("Error al verificar el encargado");
        }
        
        $check_stmt->bind_param("i", $encargado);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            throw new Exception("El encargado especificado no existe");
        }
        
        $check_stmt->close();
        
        // Insertar el pedido
        $stmt = $conn->prepare("INSERT INTO pedidos (curso, retirante, encargado) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta del pedido: " . $conn->error);
        }
        
        $stmt->bind_param("ssi", $curso, $retirante, $encargado);
        if (!$stmt->execute()) {
            throw new Exception("Error al crear el pedido: " . $stmt->error);
        }
        
        $id_pedido = $conn->insert_id;
        $stmt->close();
        
        // Insertar los detalles del pedido y actualizar el inventario
        foreach ($items as $item) {
            $herramienta_id = $item['herramienta'];
            $cantidad = $item['cantidad'];
            
            // Verificar stock disponible
            $stmt = $conn->prepare("SELECT cantidad FROM herramientas WHERE codigo = ?");
            $stmt->bind_param("i", $herramienta_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stock = $result->fetch_assoc()['cantidad'];
            
            if ($stock < $cantidad) {
                throw new Exception("Stock insuficiente para la herramienta con código: " . $herramienta_id);
            }
            
            // Insertar detalle
            $stmt = $conn->prepare("INSERT INTO detalle_pedido (id_pedido, herramienta, cantidad) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id_pedido, $herramienta_id, $cantidad);
            $stmt->execute();
            
            // Actualizar inventario
            $nueva_cantidad = $stock - $cantidad;
            $stmt = $conn->prepare("UPDATE herramientas SET cantidad = ? WHERE codigo = ?");
            $stmt->bind_param("ii", $nueva_cantidad, $herramienta_id);
            $stmt->execute();
        }
        
        // Confirmar transacción
        $conn->commit();
        $mensaje = "Pedido creado exitosamente";
        
    } catch (Exception $e) {
        // Revertir cambios en caso de error
        $conn->rollback();
        $resultado = false;
        $mensaje = "Error: " . $e->getMessage();
        error_log("Error en crearPedido: " . $e->getMessage());
    } finally {
        if ($conn) {
            $conn->close();
        }
    }
    
    return ['success' => $resultado, 'mensaje' => $mensaje];
}
?>
