<?php

namespace App\Modules;

class ModuleRegistry
{
    public static function all(): array
    {
        return [
            [
                'key'         => 'rotinas',
                'name'        => 'Rotinas Operacionais',
                'description' => 'Gestão de checklists, atividades e categorias operacionais',
                'icon'        => 'bi-check2-square',
                'route'       => 'checklist',
                'active'      => true,
            ],
            [
                'key'         => 'purchase_requests',
                'name'        => 'Solicitação de Compras',
                'description' => 'Registro e acompanhamento de solicitações de compras',
                'icon'        => 'bi-cart-plus',
                'route'       => 'purchase-requests.index',
                'active'      => true,
            ],
            [
                'key'         => 'shifts',
                'name'        => 'Escala de Funcionários',
                'description' => 'Planejamento e visualização de escalas de trabalho',
                'icon'        => 'bi-calendar-month',
                'route'       => 'shifts.calendar',
                'active'      => true,
            ],
            [
                'key'         => 'time_clock',
                'name'        => 'Ponto',
                'description' => 'Registro de ponto e controle de horas trabalhadas',
                'icon'        => 'bi-clock-history',
                'route'       => 'time-entries.dashboard',
                'active'      => true,
            ],
            [
                'key'         => 'estoque',
                'name'        => 'Controle de Estoque',
                'description' => 'Gestão de produtos, entradas e saídas de estoque',
                'icon'        => 'bi-box-seam',
                'route'       => 'estoque.index',
                'active'      => true,
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return collect(static::all())->firstWhere('key', $key);
    }

    public static function active(): array
    {
        return array_filter(static::all(), fn($m) => $m['active']);
    }
}