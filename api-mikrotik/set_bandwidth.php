<?php
session_start();
require('routeros_api.class.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ip = $_POST['ip_bandwidth'];
    $download_limit = $_POST['download_limit']; // en kbps
    $upload_limit = $_POST['upload_limit']; // en kbps

    // Convertir los límites de ancho de banda a bps (MikroTik usa bps en lugar de kbps)
    $download_limit_bps = $download_limit * 1000;
    $upload_limit_bps = $upload_limit * 1000;

    $API = new RouterosAPI();

    if ($API->connect('192.168.1.85', 'admin', 'admin')) {
        // Buscar cola existente
        $result = $API->comm('/queue/simple/print', [
            '?target' => $ip . '/32',
        ]);
    
        if (empty($result)) {
            // Crear nueva cola
            $API->comm('/queue/simple/add', [
                'name' => "Limit_" . $ip,
                'target' => $ip . '/32',
                'max-limit' => $upload_limit_bps . '/' . $download_limit_bps,
            ]);
            error_log("Nueva cola creada para la IP $ip.");
            $_SESSION['message'] = "Ancho de banda configurado para IP: $ip.";
            $_SESSION['alert_class'] = "alert-success";
        } else {
            // Actualizar cola existente
            $queue_id = $result[0]['.id'];
            $API->comm('/queue/simple/set', [
                '.id' => $queue_id,
                'max-limit' => $upload_limit_bps . '/' . $download_limit_bps,
            ]);
            error_log("Cola existente actualizada para la IP $ip.");
            $_SESSION['message'] = "Límite de ancho de banda actualizado para IP: $ip.";
            $_SESSION['alert_class'] = "alert-success";
        }
    
        $API->disconnect();
    } else {
        error_log("No se pudo conectar al router MikroTik.");
        $_SESSION['message'] = "No se pudo conectar con el router.";
        $_SESSION['alert_class'] = "alert-danger";
    }
    
    
    header("Location: index.php");
    exit;
}
?>
