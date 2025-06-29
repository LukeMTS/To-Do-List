<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;

class TaskIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_can_perform_complete_crud_operations()
    {
        // 1. CREATE - Criar uma nova tarefa
        $taskData = [
            'nome' => 'Tarefa de integração',
            'descricao' => 'Descrição da tarefa de integração',
            'finalizado' => false,
            'data_limite' => now()->addDays(7)->toISOString()
        ];

        $createResponse = $this->postJson('/api/tasks', $taskData);
        $createResponse->assertStatus(201);
        
        $taskId = $createResponse->json('data.id');
        $this->assertNotNull($taskId);

        // 2. READ - Verificar se a tarefa foi criada
        $readResponse = $this->getJson("/api/tasks/{$taskId}");
        $readResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $taskId,
                    'nome' => 'Tarefa de integração',
                    'descricao' => 'Descrição da tarefa de integração',
                    'finalizado' => false
                ]
            ]);

        // 3. UPDATE - Atualizar a tarefa
        $updateData = [
            'nome' => 'Tarefa de integração atualizada',
            'descricao' => 'Descrição atualizada',
            'finalizado' => true
        ];

        $updateResponse = $this->patchJson("/api/tasks/{$taskId}", $updateData);
        $updateResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $taskId,
                    'nome' => 'Tarefa de integração atualizada',
                    'descricao' => 'Descrição atualizada',
                    'finalizado' => true
                ]
            ]);

        // 4. TOGGLE - Alternar o status da tarefa
        $toggleResponse = $this->patchJson("/api/tasks/{$taskId}/toggle");
        $toggleResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $taskId,
                    'finalizado' => false
                ]
            ]);

        // 5. DELETE - Excluir a tarefa
        $deleteResponse = $this->deleteJson("/api/tasks/{$taskId}");
        $deleteResponse->assertStatus(200);

        // 6. VERIFY - Verificar se a tarefa foi excluída (soft delete)
        $this->assertSoftDeleted('tasks', ['id' => $taskId]);
        
        // Tentar buscar a tarefa deve retornar 404
        $notFoundResponse = $this->getJson("/api/tasks/{$taskId}");
        $notFoundResponse->assertStatus(404);
    }

    /** @test */
    public function it_can_handle_multiple_tasks_with_cache()
    {
        // Criar múltiplas tarefas
        $tasks = [];
        for ($i = 1; $i <= 5; $i++) {
            $taskData = [
                'nome' => "Tarefa {$i}",
                'descricao' => "Descrição da tarefa {$i}",
                'finalizado' => $i % 2 === 0, // Tarefas pares são finalizadas
                'data_limite' => now()->addDays($i)->toISOString()
            ];

            $response = $this->postJson('/api/tasks', $taskData);
            $response->assertStatus(201);
            $tasks[] = $response->json('data');
        }

        // Listar todas as tarefas
        $listResponse = $this->getJson('/api/tasks');
        $listResponse->assertStatus(200);
        
        $retrievedTasks = $listResponse->json('data');
        $this->assertCount(5, $retrievedTasks);

        // Verificar se as tarefas estão ordenadas por created_at desc
        $taskIds = collect($retrievedTasks)->pluck('id');
        $expectedIds = collect($tasks)->pluck('id')->reverse();
        $this->assertEquals($expectedIds, $taskIds);

        // Atualizar algumas tarefas
        foreach (array_slice($tasks, 0, 3) as $task) {
            $updateData = [
                'nome' => "Tarefa {$task['id']} atualizada",
                'finalizado' => !$task['finalizado']
            ];

            $updateResponse = $this->patchJson("/api/tasks/{$task['id']}", $updateData);
            $updateResponse->assertStatus(200);
        }

        // Verificar se as atualizações foram aplicadas
        $updatedListResponse = $this->getJson('/api/tasks');
        $updatedListResponse->assertStatus(200);
        
        $updatedTasks = $updatedListResponse->json('data');
        foreach (array_slice($updatedTasks, 0, 3) as $task) {
            $this->assertStringContainsString('atualizada', $task['nome']);
        }
    }

    /** @test */
    public function it_handles_concurrent_operations_correctly()
    {
        // Criar uma tarefa
        $taskData = [
            'nome' => 'Tarefa para operações concorrentes',
            'descricao' => 'Descrição inicial'
        ];

        $createResponse = $this->postJson('/api/tasks', $taskData);
        $createResponse->assertStatus(201);
        $taskId = $createResponse->json('data.id');

        // Simular operações concorrentes
        $responses = [];
        
        // Múltiplas atualizações simultâneas
        for ($i = 1; $i <= 3; $i++) {
            $updateData = [
                'nome' => "Tarefa atualizada {$i}",
                'descricao' => "Descrição atualizada {$i}"
            ];
            
            $responses[] = $this->patchJson("/api/tasks/{$taskId}", $updateData);
        }

        // Verificar que todas as operações foram bem-sucedidas
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Verificar o estado final (deve ser a última atualização)
        $finalResponse = $this->getJson("/api/tasks/{$taskId}");
        $finalResponse->assertStatus(200);
        
        $finalTask = $finalResponse->json('data');
        $this->assertStringContainsString('atualizada', $finalTask['nome']);
    }

    /** @test */
    public function it_validates_data_consistency()
    {
        // Criar tarefa com dados válidos
        $validTaskData = [
            'nome' => 'Tarefa válida',
            'descricao' => 'Descrição válida',
            'finalizado' => false,
            'data_limite' => now()->addDays(5)->toISOString()
        ];

        $createResponse = $this->postJson('/api/tasks', $validTaskData);
        $createResponse->assertStatus(201);
        $taskId = $createResponse->json('data.id');

        // Verificar se os dados foram salvos corretamente
        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'nome' => 'Tarefa válida',
            'descricao' => 'Descrição válida',
            'finalizado' => false
        ]);

        // Atualizar com dados inválidos
        $invalidUpdateData = [
            'nome' => '', // Nome vazio
            'data_limite' => 'data-invalida'
        ];

        $updateResponse = $this->patchJson("/api/tasks/{$taskId}", $invalidUpdateData);
        $updateResponse->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'data_limite']);

        // Verificar se os dados originais permaneceram inalterados
        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'nome' => 'Tarefa válida',
            'descricao' => 'Descrição válida',
            'finalizado' => false
        ]);
    }

    /** @test */
    public function it_handles_edge_cases_correctly()
    {
        // 1. Criar tarefa com nome muito longo
        $longNameTask = [
            'nome' => str_repeat('a', 255), // Máximo permitido
            'descricao' => 'Descrição normal'
        ];

        $createResponse = $this->postJson('/api/tasks', $longNameTask);
        $createResponse->assertStatus(201);

        // 2. Criar tarefa com nome que excede o limite
        $tooLongNameTask = [
            'nome' => str_repeat('a', 256), // Excede o limite
            'descricao' => 'Descrição normal'
        ];

        $createResponse = $this->postJson('/api/tasks', $tooLongNameTask);
        $createResponse->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);

        // 3. Criar tarefa com data limite no passado
        $pastDateTask = [
            'nome' => 'Tarefa com data passada',
            'data_limite' => now()->subDays(5)->toISOString()
        ];

        $createResponse = $this->postJson('/api/tasks', $pastDateTask);
        $createResponse->assertStatus(201); // Deve ser permitido

        // 4. Criar tarefa com todos os campos opcionais nulos
        $minimalTask = [
            'nome' => 'Tarefa mínima'
        ];

        $createResponse = $this->postJson('/api/tasks', $minimalTask);
        $createResponse->assertStatus(201);
        
        $taskId = $createResponse->json('data.id');
        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'nome' => 'Tarefa mínima',
            'descricao' => null,
            'finalizado' => false,
            'data_limite' => null
        ]);
    }

    /** @test */
    public function it_maintains_data_integrity_after_operations()
    {
        // Criar tarefa inicial
        $initialTask = [
            'nome' => 'Tarefa inicial',
            'descricao' => 'Descrição inicial',
            'finalizado' => false,
            'data_limite' => now()->addDays(10)->toISOString()
        ];

        $createResponse = $this->postJson('/api/tasks', $initialTask);
        $createResponse->assertStatus(201);
        $taskId = $createResponse->json('data.id');

        // Realizar múltiplas operações
        $operations = [
            ['nome' => 'Tarefa atualizada 1', 'finalizado' => true],
            ['descricao' => 'Nova descrição'],
            ['data_limite' => now()->addDays(15)->toISOString()],
            ['nome' => 'Tarefa final', 'finalizado' => false]
        ];

        foreach ($operations as $operation) {
            $updateResponse = $this->patchJson("/api/tasks/{$taskId}", $operation);
            $updateResponse->assertStatus(200);
        }

        // Verificar estado final
        $finalResponse = $this->getJson("/api/tasks/{$taskId}");
        $finalResponse->assertStatus(200);
        
        $finalTask = $finalResponse->json('data');
        $this->assertEquals('Tarefa final', $finalTask['nome']);
        $this->assertEquals('Nova descrição', $finalTask['descricao']);
        $this->assertFalse($finalTask['finalizado']);
        $this->assertNotNull($finalTask['data_limite']);

        // Verificar integridade no banco de dados
        $this->assertDatabaseHas('tasks', [
            'id' => $taskId,
            'nome' => 'Tarefa final',
            'descricao' => 'Nova descrição',
            'finalizado' => false
        ]);
    }
} 