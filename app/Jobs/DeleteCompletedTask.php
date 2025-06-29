<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class DeleteCompletedTask implements ShouldQueue
{
    use Queueable;

    protected $taskId;

    /**
     * Create a new job instance.
     */
    public function __construct($taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $task = Task::withTrashed()->find($this->taskId);
        if ($task && $task->finalizado) {
            $task->forceDelete();
        }

        Cache::forget('tasks:index');

        if ($this->taskId) {
            Cache::forget("tasks:show:$this->taskId");
        }
    }
}
