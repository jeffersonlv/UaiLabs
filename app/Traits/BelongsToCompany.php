<?php
namespace App\Traits;

trait BelongsToCompany
{
    public function assertBelongsToCompany(): void
    {
        $companyId = auth()->user()?->company_id;
        if ($this->company_id !== $companyId) {
            abort(403, 'Acesso negado: recurso não pertence à sua empresa.');
        }
    }
}
