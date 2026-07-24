<?php

session_start();
include __DIR__ . '/dbcon.php';

if (isset($_GET['action']) && $_GET['action'] === 'getinstallers') {
    header('Content-Type: application/json');

    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $sql = "
            SELECT 
                id,
                app_name,
                version,
                date_updated,
                download_link
            FROM BS_installers
            WHERE is_active = 1 AND date_updated IS NOT NULL
            ORDER BY date_updated DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $installers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($installers ?: []);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Application error', 'message' => $e->getMessage()]);
    }
    exit();
}


if (isset($_GET['action']) && $_GET['action'] === 'getinstallers_it') {
    header('Content-Type: application/json');

    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['error' => 'Database connection failed']);
        exit();
    }

    try {
        $sql = "
            SELECT 
                id,
                app_name,
                version,
                date_updated,
                download_link
            FROM BS_installers
            WHERE is_active = 1 AND date_updated IS NULL
            ORDER BY date_updated DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        $installers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($installers ?: []);

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Application error', 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['error' => 'Invalid action']);