<?php
header('Content-Type: application/json; charset=utf-8');

try {
    session_start();
    
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != "Admin") {
        echo json_encode(['error' => 'Acceso denegado']);
        exit();
    }

    require_once '../../Config/dbConection.php';

    $userId = (int)$_SESSION['user_id'];
    
    // Consulta para PostgreSQL con STRING_AGG
    $sql = "SELECT 
                mc.materia_ciclo_id,
                m.materia_id,
                m.nombre AS materia,
                m.codigo,
                mc.horas_teoricas,
                mc.horas_practicas,
                mc.horas_totales,
                mc.fecha_asignacion,
                c.nombre AS ciclo_nombre,
                STRING_AGG(DISTINCT u2.nombre, ', ') AS usuarios_asignados,
                COUNT(DISTINCT umc.usuario_id) AS total_usuarios
            FROM materia_ciclo mc
            INNER JOIN materia m ON mc.materia_id = m.materia_id
            INNER JOIN ciclo c ON mc.ciclo_id = c.ciclo_id
            LEFT JOIN usuario_materia_ciclo umc ON mc.materia_ciclo_id = umc.materia_ciclo_id
            LEFT JOIN usuario u2 ON umc.usuario_id = u2.usuario_id
            WHERE mc.usuario_id = ?
            AND m.activo = true
            GROUP BY mc.materia_ciclo_id, m.materia_id, m.nombre, m.codigo, mc.horas_teoricas, mc.horas_practicas, mc.horas_totales, mc.fecha_asignacion, c.nombre
            ORDER BY mc.fecha_asignacion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    
    $materias = [];
    while ($row = $stmt->fetch()) {
        $materias[] = [
            'materia_ciclo_id' => (int)$row['materia_ciclo_id'],
            'materia_id' => (int)$row['materia_id'],
            'materia' => $row['materia'],
            'codigo' => $row['codigo'],
            'horas_teoricas' => (int)$row['horas_teoricas'],
            'horas_practicas' => (int)$row['horas_practicas'],
            'horas_totales' => (int)$row['horas_totales'],
            'ciclo_nombre' => $row['ciclo_nombre'],
            'fecha_asignacion' => $row['fecha_asignacion'],
            'usuarios_asignados' => $row['usuarios_asignados'] ?? 'Sin asignar',
            'total_usuarios' => (int)$row['total_usuarios']
        ];
    }

    echo json_encode($materias, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>