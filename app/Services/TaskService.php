<?php

namespace App\Services;

use App\Models\Task;
use App\Jobs\DeleteCompletedTask;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class TaskService
{
    const CACHE_TTL = 60; // 1 minuto

    /**
     * Get all tasks with cache
     */
    public function getAllTasks()
    {
        return Cache::remember('tasks:index', self::CACHE_TTL, function () {
            return Task::whereNull('deleted_at')->orderByDesc('created_at')->get();
        });
    }

    /**
     * Get task by ID with cache
     */
    public function getTaskById($id)
    {
        return Cache::remember("tasks:show:$id", self::CACHE_TTL, function () use ($id) {
            $task = Task::find($id);
            if (!$task) {
                throw new ModelNotFoundException('Tarefa não encontrada.');
            }
            return $task;
        });
    }

    /**
     * Create a new task
     */
    public function createTask(array $data)
    {
        try {
            $task = Task::create($data);
            
            $this->invalidateCache();
            return $task;
        } catch (Exception $e) {
            throw new Exception('Erro ao criar tarefa: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing task
     */
    public function updateTask($id, array $data)
    {
        try {
            $task = Task::findOrFail($id);
            
            $task->update($data);
            
            $this->invalidateCache($id);
            return $task->fresh();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Tarefa não encontrada.');
        } catch (Exception $e) {
            throw new Exception('Erro ao atualizar tarefa: ' . $e->getMessage());
        }
    }

    /**
     * Delete a task (soft delete)
     */
    public function deleteTask($id)
    {
        try {
            $task = Task::findOrFail($id);
            $task->delete();
            
            $this->invalidateCache($id);
            return true;
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Tarefa não encontrada.');
        } catch (Exception $e) {
            throw new Exception('Erro ao excluir tarefa: ' . $e->getMessage());
        }
    }

    /**
     * Toggle task completion status
     */
    public function toggleTask($id)
    {
        try {
            $task = Task::findOrFail($id);
            $task->finalizado = !$task->finalizado;
            $task->save();
            
            // Disparar job de exclusão se finalizada
            if ($task->finalizado) {
                DeleteCompletedTask::dispatch($task->id)->delay(30);
            }
            
            $this->invalidateCache($id);
            return $task;
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('Tarefa não encontrada.');
        } catch (Exception $e) {
            throw new Exception('Erro ao alterar status da tarefa: ' . $e->getMessage());
        }
    }

    /**
     * Invalidate all task cache
     */
    private function invalidateCache($id = null)
    {
        Cache::forget('tasks:index');

        if ($id) {
            Cache::forget("tasks:show:$id");
        }
    }
} 