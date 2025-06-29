<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $tasks = $this->taskService->getAllTasks();
            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'Tarefas listadas com sucesso.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar tarefas.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar tarefa.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($id);
            
            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Tarefa encontrada com sucesso.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar tarefa.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma tarefa existente.
     * @param Request $request
     * @param Task $task
     * @return JsonResponse
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        try {
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar tarefa.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @param Task $task
     * @return JsonResponse
     */
    public function destroy(Task $task): JsonResponse
    {
        try {
            $this->taskService->deleteTask($task->id);
            return response()->json([
                'success' => true,
                'message' => 'Tarefa excluída com sucesso.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir tarefa.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alterna o status de finalização de uma tarefa.
     * @param Task $task
     * @return JsonResponse
     */
    public function toggle(Task $task): JsonResponse
    {
        try {
            $updatedTask = $this->taskService->toggleTask($task->id);
            return response()->json([
                'success' => true,
                'data' => $updatedTask,
                'message' => $updatedTask->finalizado ? 'Tarefa marcada como finalizada.' : 'Tarefa marcada como não finalizada.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alternar status da tarefa.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
