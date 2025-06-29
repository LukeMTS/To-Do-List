<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // Limpar cache antes de cada teste
    }

    /** @test */
    public function it_can_list_all_tasks()
    {
        // Arrange
        $tasks = Task::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/tasks');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'nome',
                        'descricao',
                        'finalizado',
                        'data_limite',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tarefas listadas com sucesso.'
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_can_create_a_new_task()
    {
        // Arrange
        $taskData = [
            'nome' => 'Tarefa de teste',
            'descricao' => 'Descrição da tarefa de teste',
            'finalizado' => false,
            'data_limite' => now()->addDays(7)->toISOString()
        ];

        // Act
        $response = $this->postJson('/api/tasks', $taskData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'nome',
                    'descricao',
                    'finalizado',
                    'data_limite',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tarefa criada com sucesso.',
                'data' => [
                    'nome' => 'Tarefa de teste',
                    'descricao' => 'Descrição da tarefa de teste',
                    'finalizado' => false
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'nome' => 'Tarefa de teste',
            'descricao' => 'Descrição da tarefa de teste',
            'finalizado' => false
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_task()
    {
        // Act
        $response = $this->postJson('/api/tasks', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);
    }

    /** @test */
    public function it_validates_nome_max_length_when_creating_task()
    {
        // Arrange
        $taskData = [
            'nome' => str_repeat('a', 256), // Excede o limite de 255
            'descricao' => 'Descrição válida'
        ];

        // Act
        $response = $this->postJson('/api/tasks', $taskData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);
    }

    /** @test */
    public function it_can_show_a_specific_task()
    {
        // Arrange
        $task = Task::factory()->create();

        // Act
        $response = $this->getJson("/api/tasks/{$task->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'nome',
                    'descricao',
                    'finalizado',
                    'data_limite',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tarefa encontrada com sucesso.',
                'data' => [
                    'id' => $task->id,
                    'nome' => $task->nome,
                    'descricao' => $task->descricao,
                    'finalizado' => $task->finalizado
                ]
            ]);
    }

    /** @test */
    public function it_returns_404_when_task_not_found()
    {
        // Act
        $response = $this->getJson('/api/tasks/999');

        // Assert
        $response->assertStatus(404);
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
            'finalizado' => true,
            'data_limite' => now()->addDays(14)->toISOString()
        ];

        // Act
        $response = $this->patchJson("/api/tasks/{$task->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'nome',
                    'descricao',
                    'finalizado',
                    'data_limite',
                    'created_at',
                    'updated_at'
                ],
                'message'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Tarefa atualizada com sucesso.',
                'data' => [
                    'id' => $task->id,
                    'nome' => 'Tarefa atualizada',
                    'descricao' => 'Descrição atualizada',
                    'finalizado' => true
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'nome' => 'Tarefa atualizada',
            'descricao' => 'Descrição atualizada',
            'finalizado' => true
        ]);
    }

    /** @test */
    public function it_can_partially_update_a_task()
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
        $response = $this->patchJson("/api/tasks/{$task->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $task->id,
                    'nome' => 'Apenas nome atualizado',
                    'descricao' => 'Descrição original', // Deve permanecer inalterado
                    'finalizado' => false // Deve permanecer inalterado
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'nome' => 'Apenas nome atualizado',
            'descricao' => 'Descrição original',
            'finalizado' => false
        ]);
    }

    /** @test */
    public function it_can_delete_a_task()
    {
        // Arrange
        $task = Task::factory()->create();

        // Act
        $response = $this->deleteJson("/api/tasks/{$task->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tarefa excluída com sucesso.'
            ]);

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id
        ]);
    }

    /** @test */
    public function it_can_toggle_task_completion_status()
    {
        // Arrange
        $task = Task::factory()->create([
            'finalizado' => false
        ]);

        // Act - Toggle para finalizado
        $response = $this->patchJson("/api/tasks/{$task->id}/toggle");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tarefa marcada como finalizada.',
                'data' => [
                    'id' => $task->id,
                    'finalizado' => true
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'finalizado' => true
        ]);

        // Act - Toggle de volta para não finalizado
        $response = $this->patchJson("/api/tasks/{$task->id}/toggle");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tarefa marcada como não finalizada.',
                'data' => [
                    'id' => $task->id,
                    'finalizado' => false
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'finalizado' => false
        ]);
    }

    /** @test */
    public function it_returns_404_when_trying_to_update_nonexistent_task()
    {
        // Arrange
        $updateData = [
            'nome' => 'Tarefa inexistente'
        ];

        // Act
        $response = $this->patchJson('/api/tasks/999', $updateData);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_when_trying_to_delete_nonexistent_task()
    {
        // Act
        $response = $this->deleteJson('/api/tasks/999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_when_trying_to_toggle_nonexistent_task()
    {
        // Act
        $response = $this->patchJson('/api/tasks/999/toggle');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_data_limite_format()
    {
        // Arrange
        $taskData = [
            'nome' => 'Tarefa válida',
            'data_limite' => 'data-invalida'
        ];

        // Act
        $response = $this->postJson('/api/tasks', $taskData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data_limite']);
    }

    /** @test */
    public function it_accepts_null_data_limite()
    {
        // Arrange
        $taskData = [
            'nome' => 'Tarefa sem data limite',
            'descricao' => 'Descrição válida',
            'data_limite' => null
        ];

        // Act
        $response = $this->postJson('/api/tasks', $taskData);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'nome' => 'Tarefa sem data limite',
                    'data_limite' => null
                ]
            ]);
    }
} 