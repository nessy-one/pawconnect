<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '/../php/db.php'; // same pattern as admin_rfid.php — gives $conn (mysqli)

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'vet') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated as a vet.']);
    exit();
}

$action = $_GET['action'] ?? '';
try {
    if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {

        $sql = "SELECT mr.id, mr.rfid_tag_id, mr.vet_id, u.name AS vet_name,
                    mr.note, mr.status, mr.created_at
                FROM medical_records mr
                LEFT JOIN users u ON u.id = mr.vet_id
                ORDER BY mr.rfid_tag_id ASC, mr.created_at DESC, mr.id DESC";
        $result = mysqli_query($conn, $sql);
        if (!$result) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
            exit();
        }
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
        echo json_encode(['success' => true, 'records' => $rows]);
        exit();
    }

    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {

        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $rfidTagId = isset($body['rfid_tag_id']) ? (int) $body['rfid_tag_id'] : 0;
        $note      = trim($body['note'] ?? '');
        $status    = $body['status'] ?? 'due';

        if ($rfidTagId <= 0 || $note === '') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'An animal tag and a note are required.']);
            exit();
        }
        if (!in_array($status, ['updated', 'due'], true)) {
            $status = 'due';
        }

        $check = mysqli_prepare($conn, "SELECT id FROM rfid_tags WHERE id = ?");
        mysqli_stmt_bind_param($check, "i", $rfidTagId);
        mysqli_stmt_execute($check);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($check))) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'That RFID tag was not found.']);
            exit();
        }

        $vetId = (int) $_SESSION['user_id'];

        $insert = mysqli_prepare($conn, "INSERT INTO medical_records (rfid_tag_id, vet_id, note, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($insert, "iiss", $rfidTagId, $vetId, $note, $status);
        if (!mysqli_stmt_execute($insert)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
            exit();
        }
        $newId = mysqli_insert_id($conn);

        $fetch = mysqli_prepare($conn, "SELECT mr.id, mr.rfid_tag_id, mr.vet_id, u.name AS vet_name,
                                                mr.note, mr.status, mr.created_at
                                        FROM medical_records mr
                                        LEFT JOIN users u ON u.id = mr.vet_id
                                        WHERE mr.id = ?");
        mysqli_stmt_bind_param($fetch, "i", $newId);
        mysqli_stmt_execute($fetch);
        $record = mysqli_fetch_assoc(mysqli_stmt_get_result($fetch));

        echo json_encode(['success' => true, 'record' => $record]);
        exit();
    }
} catch (\Throwable $e) {
    error_log('vet_records.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown or unsupported action.']);