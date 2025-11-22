<?php

if (!function_exists('currentProfessorRecord')) {
    function currentProfessorRecord(PDO $pdo): ?array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $userId = $_SESSION['user']['id'] ?? null;
        if (!$userId) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT * FROM professors WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $cache = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        return $cache;
    }
}

if (!function_exists('requireProfessorRecord')) {
    function requireProfessorRecord(PDO $pdo): array
    {
        $record = currentProfessorRecord($pdo);
        if ($record) {
            return $record;
        }

        http_response_code(403);
        echo 'Professor record not found. Please contact the registrar.';
        exit;
    }
}

if (!function_exists('professorFullName')) {
    function professorFullName(array $professor): string
    {
        $parts = [
            trim($professor['first_name'] ?? ''),
            trim($professor['middle_name'] ?? ''),
            trim($professor['last_name'] ?? ''),
        ];

        return trim(implode(' ', array_filter($parts)));
    }
}
