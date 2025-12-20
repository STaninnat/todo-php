<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
use InvalidArgumentException;

/**
 * Class BulkDeleteTaskService
 *
 * Handles the logic for deleting multiple tasks.
 *
 * @package App\Api\Tasks\Service
 */
class BulkDeleteTaskService
{
    /** @var TaskQueries Database handler */
    private TaskQueries $taskQueries;

    public function __construct(TaskQueries $taskQueries)
    {
        $this->taskQueries = $taskQueries;
    }

    /**
     * Execute bulk delete.
     *
     * @param Request $req
     * 
     * @return array<string, int>
     */
    public function execute(Request $req): array
    {
        $ids = RequestValidator::getArray($req, 'ids', 'IDs must be an array.');
        $userId = RequestValidator::getAuthUserId($req);

        // Sanitize IDs to ensure they are integers
        $cleanIds = array_map('intval', array_filter($ids, fn($id) => is_numeric($id)));
        if (empty($cleanIds)) {
            return ['count' => 0];
        }

        if (count($cleanIds) > 50) {
            throw new InvalidArgumentException("Cannot delete more than 50 tasks at once.");
        }

        $result = $this->taskQueries->deleteTasks($cleanIds, $userId);
        RequestValidator::ensureSuccess($result, 'bulk delete tasks', false);

        return [
            'count' => $result->affected
        ];
    }
}
