<?php
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\TaskOccurrenceController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ModulePermissionController;
use App\Http\Controllers\Admin\SupportRequestAdminController;
use App\Http\Controllers\Admin\UnitController as AdminUnitController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/company-inactive', fn() => view('company-inactive'))->name('company.inactive');

    Route::middleware(['company.active', 'throttle:web-auth'])->group(function () {
        Route::get('/', fn() => redirect()->route('dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/password/edit', fn() => view('auth.password-edit'))->name('password.edit');
        Route::get('/activity-log', [AuditLogController::class, 'index'])->name('audit-log.index');

        // ── Módulo: Rotinas Operacionais ─────────────────────────
        Route::middleware('module:rotinas')->group(function () {
            Route::get('/checklist', [TaskOccurrenceController::class, 'index'])->name('checklist');
            Route::patch('/checklist/{occurrence}/complete', [TaskOccurrenceController::class, 'complete'])->name('checklist.complete');

            Route::middleware('admin')->group(function () {
                Route::resource('categories', CategoryController::class);
                Route::resource('activities', ActivityController::class);
            });
        });


        // ── Módulo: Controle de Estoque ──────────────────────────
        Route::middleware('module:estoque')->group(function () {
            Route::get('/estoque', [EstoqueController::class, 'index'])->name('estoque.index');
        });

        // ── Solicitações (manager e admin) ───────────────────────
        Route::get('/support-requests', [SupportRequestController::class, 'index'])->name('support-requests.index');
        Route::get('/support-requests/create', [SupportRequestController::class, 'create'])->name('support-requests.create');
        Route::post('/support-requests', [SupportRequestController::class, 'store'])->name('support-requests.store');
        Route::get('/support-requests/{supportRequest}', [SupportRequestController::class, 'show'])->name('support-requests.show');
        Route::post('/support-requests/{supportRequest}/notes', [SupportRequestController::class, 'addNote'])->name('support-requests.notes.store');
        Route::patch('/support-requests/{supportRequest}/close', [SupportRequestController::class, 'close'])->name('support-requests.close');

        // ── Superadmin ───────────────────────────────────────────
        Route::middleware('superadmin')->prefix('admin')->name('admin.')->group(function () {
            Route::resource('companies', CompanyController::class)->except('show');
            Route::patch('companies/{company}/toggle', [CompanyController::class, 'toggle'])->name('companies.toggle');
            Route::resource('users', UserAdminController::class)->except('show');
            Route::patch('users/{user}/toggle', [UserAdminController::class, 'toggle'])->name('users.toggle');

            // Filiais por empresa
            Route::resource('companies/{company}/units', AdminUnitController::class)->except('show')->names('units');

            // Permissões de módulos por empresa / usuário
            Route::get('companies/{company}/modules', [ModulePermissionController::class, 'index'])->name('modules.index');
            Route::post('companies/{company}/modules/role', [ModulePermissionController::class, 'updateRole'])->name('modules.updateRole');
            Route::post('users/{user}/modules', [ModulePermissionController::class, 'updateUser'])->name('modules.updateUser');

            // Solicitações (superadmin)
            Route::get('support-requests', [SupportRequestAdminController::class, 'index'])->name('support-requests.index');
            Route::get('support-requests/{supportRequest}', [SupportRequestAdminController::class, 'show'])->name('support-requests.show');
            Route::patch('support-requests/{supportRequest}', [SupportRequestAdminController::class, 'update'])->name('support-requests.update');
            Route::patch('support-requests/{supportRequest}/important', [SupportRequestAdminController::class, 'toggleImportant'])->name('support-requests.important');
            Route::post('support-requests/{supportRequest}/notes', [SupportRequestAdminController::class, 'addNote'])->name('support-requests.notes.store');
            Route::patch('support-requests/{supportRequest}/close', [SupportRequestAdminController::class, 'close'])->name('support-requests.close');
        });
    });
});

require __DIR__.'/auth.php';
