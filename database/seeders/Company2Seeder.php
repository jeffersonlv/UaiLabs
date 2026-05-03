<?php
namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Category;
use App\Models\Company;
use App\Models\TaskOccurrence;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class Company2Seeder extends Seeder
{
    public function run(): void
    {
        // ── Empresa ──────────────────────────────────────────────
        $company = Company::create([
            'name'   => 'Padaria & Confeitaria Doce Pão',
            'slug'   => 'doce-pao',
            'email'  => 'contato@docepao.com',
            'phone'  => '(11) 98888-1234',
            'active' => true,
        ]);

        // ── Unidades ─────────────────────────────────────────────
        $unitA = Unit::create(['company_id' => $company->id, 'name' => 'Matriz Vila Madalena', 'address' => 'Rua Harmonia, 55',  'active' => true]);
        $unitB = Unit::create(['company_id' => $company->id, 'name' => 'Filial Pinheiros',     'address' => 'Rua dos Pinheiros, 300', 'active' => true]);

        // ── Usuários ─────────────────────────────────────────────
        $admin = User::create(['name' => 'Admin Doce Pão', 'username' => 'admin_dp',  'email' => 'admin@docepao.com',   'password' => Hash::make('password'), 'role' => 'admin',   'company_id' => $company->id, 'active' => true]);
        $mgr1  = User::create(['name' => 'Gerente Carlos', 'username' => 'carlos_dp', 'email' => 'carlos@docepao.com',  'password' => Hash::make('password'), 'role' => 'manager', 'company_id' => $company->id, 'active' => true]);
        $mgr2  = User::create(['name' => 'Gerente Paula',  'username' => 'paula_dp',  'email' => 'paula@docepao.com',   'password' => Hash::make('password'), 'role' => 'manager', 'company_id' => $company->id, 'active' => true]);
        $stf1  = User::create(['name' => 'Staff Marcos',   'username' => 'marcos_dp', 'email' => 'marcos@docepao.com',  'password' => Hash::make('password'), 'role' => 'staff',   'company_id' => $company->id, 'active' => true]);
        $stf2  = User::create(['name' => 'Staff Fernanda', 'username' => 'fer_dp',    'email' => 'fernanda@docepao.com','password' => Hash::make('password'), 'role' => 'staff',   'company_id' => $company->id, 'active' => true]);
        $stf3  = User::create(['name' => 'Staff Ricardo',  'username' => 'rico_dp',   'email' => 'ricardo@docepao.com', 'password' => Hash::make('password'), 'role' => 'staff',   'company_id' => $company->id, 'active' => true]);

        $executors = [$admin->id, $mgr1->id, $mgr2->id, $stf1->id, $stf2->id, $stf3->id];

        // ── Categorias ───────────────────────────────────────────
        $catAbertura  = Category::create(['company_id' => $company->id, 'name' => 'Abertura',    'active' => true]);
        $catFechamento = Category::create(['company_id' => $company->id, 'name' => 'Fechamento',  'active' => true]);
        $catLimpeza   = Category::create(['company_id' => $company->id, 'name' => 'Limpeza',     'active' => true]);
        $catProducao  = Category::create(['company_id' => $company->id, 'name' => 'Produção',    'active' => true]);
        $catEstoque   = Category::create(['company_id' => $company->id, 'name' => 'Estoque',     'active' => true]);
        $catSeguranca = Category::create(['company_id' => $company->id, 'name' => 'Segurança',   'active' => true]);

        // ── Atividades ───────────────────────────────────────────
        $activities = [
            // Abertura (sequência obrigatória)
            ['title' => 'Destrancar e vistoriar a loja',    'category_id' => $catAbertura->id,  'sequence_required' => true,  'sequence_order' => 1, 'unit_id' => null],
            ['title' => 'Ligar fornos e equipamentos',       'category_id' => $catAbertura->id,  'sequence_required' => true,  'sequence_order' => 2, 'unit_id' => null],
            ['title' => 'Preparar vitrine de pães',          'category_id' => $catAbertura->id,  'sequence_required' => true,  'sequence_order' => 3, 'unit_id' => null],
            // Produção
            ['title' => 'Conferir temperatura dos fornos',   'category_id' => $catProducao->id,  'sequence_required' => false, 'sequence_order' => null, 'unit_id' => $unitA->id],
            ['title' => 'Preparar massa do dia',             'category_id' => $catProducao->id,  'sequence_required' => false, 'sequence_order' => null, 'unit_id' => null],
            ['title' => 'Registrar lote de produção',        'category_id' => $catProducao->id,  'sequence_required' => false, 'sequence_order' => null, 'unit_id' => null],
            // Limpeza
            ['title' => 'Higienizar bancadas de produção',   'category_id' => $catLimpeza->id,   'sequence_required' => false, 'sequence_order' => null, 'unit_id' => null],
            ['title' => 'Limpeza do piso e área de venda',   'category_id' => $catLimpeza->id,   'sequence_required' => false, 'sequence_order' => null, 'unit_id' => null],
            // Estoque
            ['title' => 'Verificar validade dos insumos',    'category_id' => $catEstoque->id,   'sequence_required' => false, 'sequence_order' => null, 'unit_id' => null],
            ['title' => 'Repor vitrine e prateleiras',       'category_id' => $catEstoque->id,   'sequence_required' => false, 'sequence_order' => null, 'unit_id' => null],
            // Segurança
            ['title' => 'Checar extintores e saídas',        'category_id' => $catSeguranca->id, 'sequence_required' => false, 'sequence_order' => null, 'unit_id' => null],
            // Fechamento
            ['title' => 'Fechar caixa e conferir troco',     'category_id' => $catFechamento->id,'sequence_required' => true,  'sequence_order' => 1, 'unit_id' => null],
            ['title' => 'Desligar equipamentos e fornos',    'category_id' => $catFechamento->id,'sequence_required' => true,  'sequence_order' => 2, 'unit_id' => null],
            ['title' => 'Trancar e acionar alarme',          'category_id' => $catFechamento->id,'sequence_required' => true,  'sequence_order' => 3, 'unit_id' => null],
        ];

        $createdActivities = [];
        foreach ($activities as $data) {
            $createdActivities[] = Activity::create(array_merge($data, [
                'company_id'  => $company->id,
                'periodicity' => 'diario',
                'active'      => true,
                'created_by'  => $admin->id,
            ]));
        }

        // ── Histórico: 30 dias de ocorrências ────────────────────
        $today = Carbon::today();

        for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);
            $isToday = $daysAgo === 0;

            foreach ($createdActivities as $activity) {
                if ($isToday) {
                    // Hoje: metade DONE, metade PENDING
                    $status = (rand(0, 1) === 1) ? 'DONE' : 'PENDING';
                } else {
                    // Dias passados: 80% DONE, 15% OVERDUE, 5% REOPENED
                    $rand = rand(1, 100);
                    if ($rand <= 80)      $status = 'DONE';
                    elseif ($rand <= 95)  $status = 'OVERDUE';
                    else                  $status = 'REOPENED';
                }

                $executor    = $executors[array_rand($executors)];
                $completedAt = ($status === 'DONE' || $status === 'REOPENED')
                    ? $date->copy()->setTime(rand(6, 21), rand(0, 59))
                    : null;

                TaskOccurrence::create([
                    'company_id'   => $company->id,
                    'unit_id'      => $activity->unit_id,
                    'activity_id'  => $activity->id,
                    'period_start' => $date->toDateString(),
                    'period_end'   => $date->toDateString(),
                    'status'       => $status,
                    'completed_by' => $completedAt ? $executor : null,
                    'completed_at' => $completedAt,
                    'justification'=> ($status === 'REOPENED') ? 'Reexecução solicitada pelo gerente' : null,
                ]);
            }
        }

        $this->command->info("Empresa 2 criada com {$company->name} — " . count($createdActivities) . " atividades, 31 dias de histórico.");
    }
}
