<?php
// Database connection
function connectDB() {
    $servername = "localhost";
    $username = "root";  // Change as needed
    $password = "";      // Change as needed
    $dbname = "panol";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Function to add a new tool
function addTool($nombre, $cantidad, $imagen = null) {
    $conn = connectDB();
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO herramientas (nombre, cantidad, imagen) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $nombre, $cantidad, $imagen);
    
    // Execute the query
    $result = $stmt->execute();
    
    // Get the ID of the newly inserted tool
    $new_tool_id = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    if ($result) {
        return $new_tool_id;
    } else {
        return false;
    }
}

// Function to edit an existing tool
function editTool($codigo, $nombre, $cantidad, $imagen = null) {
    $conn = connectDB();
    
    // Check if image is being updated
    if ($imagen) {
        $stmt = $conn->prepare("UPDATE herramientas SET nombre = ?, cantidad = ?, imagen = ? WHERE codigo = ?");
        $stmt->bind_param("sisi", $nombre, $cantidad, $imagen, $codigo);
    } else {
        // Don't update the image if not provided
        $stmt = $conn->prepare("UPDATE herramientas SET nombre = ?, cantidad = ? WHERE codigo = ?");
        $stmt->bind_param("sii", $nombre, $cantidad, $codigo);
    }
    
    // Execute the query
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Function to get a single tool by its code
function getTool($codigo) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("SELECT * FROM herramientas WHERE codigo = ?");
    $stmt->bind_param("i", $codigo);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $tool = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $tool;
}

// Function to get all tools
function getAllTools() {
    $conn = connectDB();
    
    $result = $conn->query("SELECT * FROM herramientas");
    
    $tools = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $tools[] = $row;
        }
    }
    
    $conn->close();
    
    return $tools;
}

// Function to delete a tool
function deleteTool($codigo) {
    $conn = connectDB();
    
    // First check if tool is used in any orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM detalle_pedido WHERE herramienta = ?");
    $stmt->bind_param("i", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        // Tool is in use, cannot delete
        $stmt->close();
        $conn->close();
        return false;
    }
    
    // If tool is not in use, proceed with deletion
    $stmt = $conn->prepare("DELETE FROM herramientas WHERE codigo = ?");
    $stmt->bind_param("i", $codigo);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Function to upload an image
function uploadToolImage($file) {
    $target_dir = "uploads/tools/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is an actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return false;
    }
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif" ) {
        return false;
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    } else {
        return false;
    }
}
?>