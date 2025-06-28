<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $tasks = $this->taskService->getAllTasks();
        return response()->json([
            'success' => true,
            'data' => $tasks,
            'message' => 'Tarefas listadas com sucesso.'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'finalizado' => ['boolean'],
            'data_limite' => ['nullable', 'date'],
        ]);
        
        $task = $this->taskService->createTask($validated);
        return response()->json([
            'success' => true,
            'data' => $task,
            'message' => 'Tarefa criada com sucesso.'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $task,
            'message' => 'Tarefa encontrada com sucesso.'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'finalizado' => ['boolean'],
            'data_limite' => ['nullable', 'date'],
        ]);
        
        $updatedTask = $this->taskService->updateTask($task->id, $validated);
        return response()->json([
            'success' => true,
            'data' => $updatedTask,
            'message' => 'Tarefa atualizada com sucesso.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->taskService->deleteTask($task->id);
        return response()->json([
            'success' => true,
            'message' => 'Tarefa excluída com sucesso.'
        ]);
    }

    /**
     * Toggle the completion status of a task.
     */
    public function toggle(Task $task): JsonResponse
    {
        $updatedTask = $this->taskService->toggleTask($task->id);
        
        return response()->json([
            'success' => true,
            'data' => $updatedTask,
            'message' => $updatedTask->finalizado ? 'Tarefa marcada como finalizada.' : 'Tarefa marcada como não finalizada.'
        ]);
    }
}
