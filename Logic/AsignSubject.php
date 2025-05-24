<?php
session_start();
require_once '../Config/dbConection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $materias = $_POST['materias'] ?? [];

    if (!empty($materias)) {
        foreach ($materias as $materia_id) {
            // Verificar que la materia fue asignada por un administrador
            $sql = "SELECT * FROM programa_admin WHERE programa_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $materia_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Insertar la asignación en la tabla programa_usuario
                $sql = "INSERT INTO programa_usuario (usuario_id, programa_id) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $user_id, $materia_id);
                $stmt->execute();
            }
        }
        // Redirigir correctamente a HomeUser.php
        header("Location: ../Pages/HomeUser.php?success=materias_asignadas");
    } else {
        // Redirigir correctamente a SelectSubject.php en caso de error
        header("Location: ../Pages/SelectSubject.php?error=no_materias");
    }
    exit();
}