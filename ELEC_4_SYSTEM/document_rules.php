    <?php
    // config/document_rules.php
    // Rules kung aling documents ang one-time lang (isang beses pwedeng i-request)
    //
    // IMPORTANTE: Ang document type strings dito ay kailangang
    // EXACT MATCH sa `request_type` column ng `requests` table.

    function getDocumentRules(): array {
        return [
            // ============================================
            // ONE-TIME DOCUMENTS (isang beses lang pwedeng i-request)
            // ============================================
            'TOR' => [
                'label'       => 'Transcript of Records',
                'is_one_time' => true,
            ],
            'Good Moral Certificate' => [
                'label'       => 'Good Moral Certificate',
                'is_one_time' => true,
            ],
            'Diploma' => [
                'label'       => 'Diploma',
                'is_one_time' => true,
            ],

            // ============================================
            // REPEATABLE DOCUMENTS (pwedeng i-request ng paulit-ulit)
            // ============================================
            'Certificate of Grades' => [
                'label'       => 'Certificate of Grades',
                'is_one_time' => false,
            ],
            'Certificate of Enrollment' => [
                'label'       => 'Certificate of Enrollment',
                'is_one_time' => false,
            ],
            'Clearance - Graduation' => [
                'label'       => 'Clearance - Graduation',
                'is_one_time' => false,
            ],
            'Clearance - Exit' => [
                'label'       => 'Clearance - Exit',
                'is_one_time' => false,
            ],
        ];
    }

    /**
     * Kunin ang existing request ng user para sa isang document type.
     * Returns array o null kung wala pa.
     *
     * NOTE: Gumagamit ng `requests` table at `request_type` column.
     */
    function getUserDocumentRequest(PDO $pdo, int $userId, string $docType): ?array {
        try {
            $stmt = $pdo->prepare(
                "SELECT * FROM requests 
                WHERE user_id = :uid AND request_type = :dtype 
                ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->execute([':uid' => $userId, ':dtype' => $docType]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("getUserDocumentRequest error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check kung pwede pa mag-request ang user.
     * Returns ['allowed' => bool, 'reason' => string, 'existing' => array|null]
     */
    function canUserRequestDocument(PDO $pdo, int $userId, string $docType): array {
        $rules = getDocumentRules();
        if (!isset($rules[$docType])) {
            return ['allowed' => false, 'reason' => 'Unknown document type.', 'existing' => null];
        }

        $rule = $rules[$docType];
        $existing = getUserDocumentRequest($pdo, $userId, $docType);

        // Kung hindi one-time → pwede pa (pero check kung may pending pa)
        if (!$rule['is_one_time']) {
            if ($existing && in_array(strtoupper($existing['status'] ?? ''), ['PENDING', 'PROCESSING'])) {
                return [
                    'allowed'  => false,
                    'reason'   => 'You have a pending request for this document. Please wait for it to be processed.',
                    'existing' => $existing,
                ];
            }
            return ['allowed' => true, 'reason' => '', 'existing' => $existing];
        }

        // One-time at may existing na → bawal
        if ($existing !== null) {
            return [
                'allowed'  => false,
                'reason'   => 'You have already requested this document. One-time issuance only.',
                'existing' => $existing,
            ];
        }

        return ['allowed' => true, 'reason' => '', 'existing' => null];
    }
    // ============================================
    // STORAGE LIMIT
    // ============================================

    /**
     * Maximum total storage per user (in bytes)
     * 50 MB = 50 * 1024 * 1024
     */
    define('MAX_USER_STORAGE', 50 * 1024 * 1024);

    /**
     * Kunin ang total storage na nagamit ng user
     * Returns bytes
     */
    function getUserStorageUsed(PDO $pdo, int $userId): int {
        try {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(file_size), 0) as total FROM document_uploads WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("getUserStorageUsed error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check kung may espasyo pa ang user para sa bagong upload
     * Returns ['allowed' => bool, 'reason' => string, 'used' => int, 'limit' => int]
     */
    function canUserUploadMore(PDO $pdo, int $userId, int $newFileBytes): array {
        $used = getUserStorageUsed($pdo, $userId);
        $limit = MAX_USER_STORAGE;
        
        if (($used + $newFileBytes) > $limit) {
            $usedMB = round($used / 1024 / 1024, 2);
            $limitMB = round($limit / 1024 / 1024, 2);
            $newMB = round($newFileBytes / 1024 / 1024, 2);
            
            return [
                'allowed' => false,
                'reason'  => "Storage limit exceeded. You have used {$usedMB}MB of {$limitMB}MB. This upload ({$newMB}MB) would exceed your limit. Please contact the registrar office.",
                'used'    => $used,
                'limit'   => $limit,
            ];
        }
        
        return [
            'allowed' => true,
            'reason'  => '',
            'used'    => $used,
            'limit'   => $limit,
        ];
    }
    ?>