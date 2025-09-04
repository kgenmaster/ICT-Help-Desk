<?php

function logActivity($pdo, $userId, $activity) {
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, activity) VALUES (?, ?)");
        $stmt->execute([$userId, $activity]);
    } catch (PDOException $e) {
        // Log the error for debugging purposes, but do not show to the user.
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>
