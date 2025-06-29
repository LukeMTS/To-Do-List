<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TaskModelTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_can_create_a_task()
    {
        // Arrange
        $taskData = [
            'nome' => 'Tarefa de teste',
            'descricao' => 'Descrição da tarefa',
            'finalizado' => false,
            'data_limite' => now()->addDays(7)
        ];

        // Act
        $task = Task::create($taskData);

        // Assert
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Tarefa de teste', $task->nome);
        $this->assertEquals('Descrição da tarefa', $task->descricao);
        $this->assertFalse($task->finalizado);
        $this->assertNotNull($task->id);
        $this->assertDatabaseHas('tasks', $taskData);
    }

    /** @test */
    public function it_can_create_task_with_minimal_data()
    {
        // Arrange
        $taskData = [
            'nome' => 'Tarefa mínima'
        ];

        // Act
        $task = Task::create($taskData);

        // Assert
        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Tarefa mínima', $task->nome);
        $this->assertNull($task->descricao);
        $this->assertFalse($task->finalizado);
        $this->assertNull($task->data_limite);
    }

    /** @test */
    public function it_casts_finalizado_to_boolean()
    {
        // Arrange
        $task = Task::create([
            'nome' => 'Tarefa para teste de cast',
            'finalizado' => 1
        ]);

        // Act
        $retrievedTask = Task::find($task->id);

        // Assert
        $this->assertTrue($retrievedTask->finalizado);
        $this->assertIsBool($retrievedTask->finalizado);
    }

    /** @test */
    public function it_casts_data_limite_to_datetime()
    {
        // Arrange
        $dateTime = now()->addDays(5);
        $task = Task::create([
            'nome' => 'Tarefa com data limite',
            'data_limite' => $dateTime
        ]);

        // Act
        $retrievedTask = Task::find($task->id);

        // Assert
        $this->assertInstanceOf(\Carbon\Carbon::class, $retrievedTask->data_limite);
        $this->assertEquals($dateTime->format('Y-m-d H:i:s'), $retrievedTask->data_limite->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_accepts_null_data_limite()
    {
        // Arrange
        $task = Task::create([
            'nome' => 'Tarefa sem data limite',
            'data_limite' => null
        ]);

        // Act
        $retrievedTask = Task::find($task->id);

        // Assert
        $this->assertNull($retrievedTask->data_limite);
    }

    /** @test */
    public function it_has_soft_deletes()
    {
        // Arrange
        $task = Task::create([
            'nome' => 'Tarefa para deletar'
        ]);

        // Act
        $task->delete();

        // Assert
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'deleted_at' => $task->fresh()->deleted_at
        ]);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete()
    {
        // Arrange
        $task = Task::create([
            'nome' => 'Tarefa para restaurar'
        ]);
        $task->delete();

        // Act
        $task->restore();

        // Assert
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'deleted_at' => null
        ]);
        $this->assertNull($task->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_be_force_deleted()
    {
        // Arrange
        $task = Task::create([
            'nome' => 'Tarefa para force delete'
        ]);

        // Act
        $task->forceDelete();

        // Assert
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function it_has_fillable_fields()
    {
        // Arrange
        $taskData = [
            'nome' => 'Tarefa fillable',
            'descricao' => 'Descrição fillable',
            'finalizado' => true,
            'data_limite' => now()->addDays(10)
        ];

        // Act
        $task = new Task();
        $task->fill($taskData);
        $task->save();

        // Assert
        $this->assertDatabaseHas('tasks', $taskData);
    }

    /** @test */
    public function it_prevents_mass_assignment_of_non_fillable_fields()
    {
        // Arrange
        $taskData = [
            'nome' => 'Tarefa com campo não fillable',
            'created_at' => now()->subDays(10), // Campo não fillable
            'updated_at' => now()->subDays(5)   // Campo não fillable
        ];

        // Act
        $task = new Task();
        $task->fill($taskData);
        $task->save();

        // Assert
        $this->assertDatabaseHas('tasks', [
            'nome' => 'Tarefa com campo não fillable'
        ]);
        
        // Os campos não fillable devem ter sido ignorados
        $retrievedTask = Task::find($task->id);
        $this->assertNotEquals($taskData['created_at'], $retrievedTask->created_at);
        $this->assertNotEquals($taskData['updated_at'], $retrievedTask->updated_at);
    }

    /** @test */
    public function it_has_timestamps()
    {
        // Arrange
        $task = Task::create([
            'nome' => 'Tarefa com timestamps'
        ]);

        // Assert
        $this->assertNotNull($task->created_at);
        $this->assertNotNull($task->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $task->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $task->updated_at);
    }

    /** @test */
    public function it_updates_timestamp_when_modified()
    {
        // Arrange
        $task = Task::create([
            'nome' => 'Tarefa original'
        ]);
        $originalUpdatedAt = $task->updated_at;

        // Wait a moment to ensure timestamp difference
        sleep(1);

        // Act
        $task->update(['nome' => 'Tarefa modificada']);

        // Assert
        $this->assertGreaterThan($originalUpdatedAt, $task->fresh()->updated_at);
    }

    /** @test */
    public function it_can_be_created_using_factory()
    {
        // Act
        $task = Task::factory()->create();

        // Assert
        $this->assertInstanceOf(Task::class, $task);
        $this->assertNotEmpty($task->nome);
        $this->assertNotNull($task->id);
    }

    /** @test */
    public function it_can_be_created_as_completed_using_factory()
    {
        // Act
        $task = Task::factory()->completed()->create();

        // Assert
        $this->assertTrue($task->finalizado);
    }

    /** @test */
    public function it_can_be_created_as_pending_using_factory()
    {
        // Act
        $task = Task::factory()->pending()->create();

        // Assert
        $this->assertFalse($task->finalizado);
    }

    /** @test */
    public function it_can_be_created_with_deadline_using_factory()
    {
        // Act
        $task = Task::factory()->withDeadline()->create();

        // Assert
        $this->assertNotNull($task->data_limite);
        $this->assertInstanceOf(\Carbon\Carbon::class, $task->data_limite);
    }

    /** @test */
    public function it_can_be_created_without_deadline_using_factory()
    {
        // Act
        $task = Task::factory()->withoutDeadline()->create();

        // Assert
        $this->assertNull($task->data_limite);
    }

    /** @test */
    public function it_can_be_created_with_long_description_using_factory()
    {
        // Act
        $task = Task::factory()->withLongDescription()->create();

        // Assert
        $this->assertNotNull($task->descricao);
        $this->assertGreaterThan(100, strlen($task->descricao));
    }

    /** @test */
    public function it_can_be_created_without_description_using_factory()
    {
        // Act
        $task = Task::factory()->withoutDescription()->create();

        // Assert
        $this->assertNull($task->descricao);
    }

    /** @test */
    public function it_can_be_created_as_urgent_using_factory()
    {
        // Act
        $task = Task::factory()->urgent()->create();

        // Assert
        $this->assertNotNull($task->data_limite);
        $this->assertLessThanOrEqual(now()->addDays(3), $task->data_limite);
        $this->assertGreaterThanOrEqual(now(), $task->data_limite);
    }

    /** @test */
    public function it_can_be_created_as_overdue_using_factory()
    {
        // Act
        $task = Task::factory()->overdue()->create();

        // Assert
        $this->assertNotNull($task->data_limite);
        $this->assertLessThan(now(), $task->data_limite);
    }
} 