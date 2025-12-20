<?php

declare(strict_types=1);

namespace App\Api\Tasks\Service;

use App\Api\Request;
use App\DB\TaskQueries;
use App\Utils\RequestValidator;
use InvalidArgumentException;

/**
 * Class BulkMarkDoneTaskService
 *
 * Handles the logic for marking multiple tasks as done/undone.
 *
 * @package App\Api\Tasks\Service
 */
class BulkMarkDoneTaskService
{
    /** @var TaskQueries Database handler */
    private TaskQueries $taskQueries;

    /**
     * Constructor.
     *
     * @param TaskQueries $taskQueries
     */
    public function __construct(TaskQueries $taskQueries)
    {
        $this->taskQueries = $taskQueries;
    }

    /**
     * Execute bulk mark done.
     *
     * @param Request $req
     * @return array<string, int>
     */
    public function execute(Request $req): array
    {
        $ids = RequestValidator::getArray($req, 'ids', 'IDs must be an array.');
        $isDone = RequestValidator::getBool($req, 'is_done', 'Invalid status value.');
        $userId = RequestValidator::getAuthUserId($req);

        // Sanitize IDs
        $cleanIds = array_map('intval', array_filter($ids, fn($id) => is_numeric($id)));
        if (empty($cleanIds)) {
            return ['count' => 0];
        }

        if (count($cleanIds) > 50) {
            throw new InvalidArgumentException("Cannot update more than 50 tasks at once.");
        }

        $result = $this->taskQueries->markTasksDone($cleanIds, $isDone, $userId);
        RequestValidator::ensureSuccess($result, 'bulk mark tasks', false);

        return [
            'count' => $result->affected
        ];
    }
}
