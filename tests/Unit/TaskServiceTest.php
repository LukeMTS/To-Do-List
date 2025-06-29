<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected TaskService $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskService = new TaskService();
        Cache::flush(); // Limpar cache antes de cada teste
    }

    /** @test */
    public function it_can_get_all_tasks()
    {
        // Arrange
        $tasks = Task::factory()->count(3)->create();

        // Act
        $result = $this->taskService->getAllTasks();

        // Assert
        $this->assertCount(3, $result);
        $this->assertEquals($tasks->pluck('id')->sort(), $result->pluck('id')->sort());
    }

    /** @test */
    public function it_returns_empty_collection_when_no_tasks_exist()
    {
        // Act
        $result = $this->taskService->getAllTasks();

        // Assert
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }

    /** @test */
    public function it_excludes_soft_deleted_tasks()
    {
        // Arrange
        $activeTask = Task::factory()->create();
        $deletedTask = Task::factory()->create();
        $deletedTask->delete(); // Soft delete

        // Act
        $result = $this->taskService->getAllTasks();

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($activeTask->id, $result->first()->id);
    }

    /** @test */
    public function it_orders_tasks_by_created_at_desc()
    {
        // Arrange
        $oldTask = Task::factory()->create(['created_at' => now()->subDays(2)]);
        $newTask = Task::factory()->create(['created_at' => now()]);
        $middleTask = Task::factory()->create(['created_at' => now()->subDay()]);

        // Act
        $result = $this->taskService->getAllTasks();

        // Assert
        $this->assertEquals($newTask->id, $result->first()->id);
        $this->assertEquals($oldTask->id, $result->last()->id);
    }

    /** @test */
    public function it_can_get_task_by_id()
    {
        // Arrange
        $task = Task::factory()->create();

        // Act
        $result = $this->taskService->getTaskById($task->id);

        // Assert
        $this->assertEquals($task->id, $result->id);
        $this->assertEquals($task->nome, $result->nome);
    }

    /** @test */
    public function it_throws_exception_when_task_not_found()
    {
        // Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Tarefa não encontrada.');

        // Act
        $this->taskService->getTaskById(999);
    }

    /** @test */
    public function it_can_create_a_new_task()
    {
        // Arrange
        $taskData = [
            'nome' => 'Nova tarefa',
            'descricao' => 'Descrição da nova tarefa',
            'finalizado' => false,
            'data_limite' => now()->addDays(7)
        ];

        // Act
        $result = $this->taskService->createTask($taskData);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals('Nova tarefa', $result->nome);
        $this->assertEquals('Descrição da nova tarefa', $result->descricao);
        $this->assertFalse($result->finalizado);
        $this->assertNotNull($result->id);

        $this->assertDatabaseHas('tasks', [
            'nome' => 'Nova tarefa',
            'descricao' => 'Descrição da nova tarefa',
            'finalizado' => false
        ]);
    }

    /** @test */
    public function it_can_create_task_with_minimal_data()
    {
        // Arrange
        $taskData = [
            'nome' => 'Tarefa mínima'
        ];

        // Act
        $result = $this->taskService->createTask($taskData);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals('Tarefa mínima', $result->nome);
        $this->assertNull($result->descricao);
        $this->assertFalse($result->finalizado);
        $this->assertNull($result->data_limite);
    }

    /** @test */
    public function it_can_update_an_existing_task()
    {
        // Arrange
        $task = Task::factory()->create([
            'nome' => 'Tarefa original',
            'descricao' => 'Descrição original',
            'finalizado' => false
        ]);

        $updateData = [
            'nome' => 'Tarefa atualizada',
            'descricao' => 'Descrição atualizada',
            'finalizado' => true
        ];

        // Act
        $result = $this->taskService->updateTask($task->id, $updateData);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals('Tarefa atualizada', $result->nome);
        $this->assertEquals('Descrição atualizada', $result->descricao);
        $this->assertTrue($result->finalizado);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'nome' => 'Tarefa atualizada',
            'descricao' => 'Descrição atualizada',
            'finalizado' => true
        ]);
    }

    /** @test */
    public function it_can_partially_update_task()
    {
        // Arrange
        $task = Task::factory()->create([
            'nome' => 'Tarefa original',
            'descricao' => 'Descrição original',
            'finalizado' => false
        ]);

        $updateData = [
            'nome' => 'Apenas nome atualizado'
        ];

        // Act
        $result = $this->taskService->updateTask($task->id, $updateData);

        // Assert
        $this->assertEquals('Apenas nome atualizado', $result->nome);
        $this->assertEquals('Descrição original', $result->descricao); // Deve permanecer inalterado
        $this->assertFalse($result->finalizado); // Deve permanecer inalterado
    }

    /** @test */
    public function it_throws_exception_when_updating_nonexistent_task()
    {
        // Arrange
        $updateData = ['nome' => 'Tarefa inexistente'];

        // Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Tarefa não encontrada.');

        // Act
        $this->taskService->updateTask(999, $updateData);
    }

    /** @test */
    public function it_can_delete_a_task()
    {
        // Arrange
        $task = Task::factory()->create();

        // Act
        $result = $this->taskService->deleteTask($task->id);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function it_throws_exception_when_deleting_nonexistent_task()
    {
        // Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Tarefa não encontrada.');

        // Act
        $this->taskService->deleteTask(999);
    }

    /** @test */
    public function it_can_toggle_task_completion_status()
    {
        // Arrange
        $task = Task::factory()->create(['finalizado' => false]);

        // Act - Toggle para finalizado
        $result = $this->taskService->toggleTask($task->id);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
        $this->assertTrue($result->finalizado);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'finalizado' => true
        ]);

        // Act - Toggle de volta para não finalizado
        $result = $this->taskService->toggleTask($task->id);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
        $this->assertFalse($result->finalizado);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'finalizado' => false
        ]);
    }

    /** @test */
    public function it_throws_exception_when_toggling_nonexistent_task()
    {
        // Assert
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('Tarefa não encontrada.');

        // Act
        $this->taskService->toggleTask(999);
    }

    /** @test */
    public function it_invalidates_cache_when_creating_task()
    {
        // Arrange
        $taskData = ['nome' => 'Tarefa para cache'];
        
        // Primeiro, vamos popular o cache
        $this->taskService->getAllTasks();

        // Act
        $this->taskService->createTask($taskData);

        // Assert - O cache deve ter sido invalidado
        $this->assertFalse(Cache::has('tasks:index'));
    }

    /** @test */
    public function it_invalidates_cache_when_updating_task()
    {
        // Arrange
        $task = Task::factory()->create();
        $updateData = ['nome' => 'Tarefa atualizada'];
        
        // Primeiro, vamos popular o cache
        $this->taskService->getAllTasks();
        $this->taskService->getTaskById($task->id);

        // Act
        $this->taskService->updateTask($task->id, $updateData);

        // Assert - O cache deve ter sido invalidado
        $this->assertFalse(Cache::has('tasks:index'));
        $this->assertFalse(Cache::has("tasks:show:{$task->id}"));
    }

    /** @test */
    public function it_invalidates_cache_when_deleting_task()
    {
        // Arrange
        $task = Task::factory()->create();
        
        // Primeiro, vamos popular o cache
        $this->taskService->getAllTasks();
        $this->taskService->getTaskById($task->id);

        // Act
        $this->taskService->deleteTask($task->id);

        // Assert - O cache deve ter sido invalidado
        $this->assertFalse(Cache::has('tasks:index'));
        $this->assertFalse(Cache::has("tasks:show:{$task->id}"));
    }

    /** @test */
    public function it_invalidates_cache_when_toggling_task()
    {
        // Arrange
        $task = Task::factory()->create();
        
        // Primeiro, vamos popular o cache
        $this->taskService->getAllTasks();
        $this->taskService->getTaskById($task->id);

        // Act
        $this->taskService->toggleTask($task->id);

        // Assert - O cache deve ter sido invalidado
        $this->assertFalse(Cache::has('tasks:index'));
        $this->assertFalse(Cache::has("tasks:show:{$task->id}"));
    }

    /** @test */
    public function it_uses_cache_for_getting_all_tasks()
    {
        // Arrange
        $tasks = Task::factory()->count(2)->create();

        // Act - Primeira chamada (deve popular o cache)
        $result1 = $this->taskService->getAllTasks();
        
        // Simular que o cache foi populado
        $this->assertTrue(Cache::has('tasks:index'));

        // Act - Segunda chamada (deve usar o cache)
        $result2 = $this->taskService->getAllTasks();

        // Assert
        $this->assertEquals($result1->count(), $result2->count());
        $this->assertEquals($result1->pluck('id'), $result2->pluck('id'));
    }

    /** @test */
    public function it_uses_cache_for_getting_task_by_id()
    {
        // Arrange
        $task = Task::factory()->create();

        // Act - Primeira chamada (deve popular o cache)
        $result1 = $this->taskService->getTaskById($task->id);
        
        // Simular que o cache foi populado
        $this->assertTrue(Cache::has("tasks:show:{$task->id}"));

        // Act - Segunda chamada (deve usar o cache)
        $result2 = $this->taskService->getTaskById($task->id);

        // Assert
        $this->assertEquals($result1->id, $result2->id);
        $this->assertEquals($result1->nome, $result2->nome);
    }
} 