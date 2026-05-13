<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ClockController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\PurchaseItemController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\TaskOccurrenceController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\WorkScheduleController;
use App\Http\Controllers\Admin\AuditLogAdminController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\GlobalDashboardController;
use App\Http\Controllers\Admin\ModulePermissionController;
use App\Http\Controllers\Admin\SupportRequestAdminController;
use App\Http\Controllers\Admin\UnitController as AdminUnitController;
use App\Http\Controllers\Admin\UserAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/company-inactive', fn() => view('company-inactive'))->name('company.inactive');

    Route::middleware(['company.active', 'throttle:web-auth'])->group(function () {
        Route::get('/', fn() => redirect()->route('dashboard'));
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/completion-chart', [DashboardController::class, 'completionChart'])->name('dashboard.chart');
        Route::get('/password/edit', fn() => view('auth.password-edit'))->name('password.edit');
        Route::get('/activity-log', [AuditLogController::class, 'index'])->name('audit-log.index');

        // ── Módulo: Rotinas Operacionais ─────────────────────────
        Route::middleware('module:rotinas')->group(function () {
            Route::get('/checklist', [TaskOccurrenceController::class, 'index'])->name('checklist');
            Route::patch('/checklist/bulk-complete', [TaskOccurrenceController::class, 'bulkComplete'])->name('checklist.bulk-complete');
            Route::patch('/checklist/{occurrence}/complete', [TaskOccurrenceController::class, 'complete'])->name('checklist.complete');
            Route::get('/checklist/{occurrence}/history', [TaskOccurrenceController::class, 'history'])->name('checklist.history');

            Route::middleware('admin')->group(function () {
                Route::get('categories/sort', [CategoryController::class, 'sort'])->name('categories.sort');
                Route::post('categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
                Route::resource('categories', CategoryController::class);

                Route::resource('subcategories', SubcategoryController::class)->except('show');

                Route::get('activities/spreadsheet', [ActivityController::class, 'spreadsheet'])->name('activities.spreadsheet');
                Route::post('activities/bulk-save', [ActivityController::class, 'bulkSave'])->name('activities.bulk-save');
                Route::patch('activities/{activity}/assign-units', [ActivityController::class, 'assignUnits'])->name('activities.assign-units');
                Route::resource('activities', ActivityController::class);
            });
        });

        // ── Módulo: Solicitação de Compras ────────────────────────
        Route::middleware('module:purchase_requests')->group(function () {
            Route::get('/purchase-items', [PurchaseItemController::class, 'index'])->name('purchase-items.index');
            Route::post('/purchase-items', [PurchaseItemController::class, 'store'])->name('purchase-items.store');
            Route::patch('/purchase-items/{purchaseItem}/toggle', [PurchaseItemController::class, 'toggle'])->name('purchase-items.toggle');
        });

        // ── Módulo: Escala de Funcionários ────────────────────────
        Route::middleware('module:shifts')->group(function () {
            Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
            Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
            Route::get('/shifts/calendar', [ShiftController::class, 'calendar'])->name('shifts.calendar');
            Route::get('/shifts/timesheet', [ShiftController::class, 'timesheet'])->name('shifts.timesheet');
            Route::get('/shifts/board', [ShiftController::class, 'board'])->name('shifts.board');
            Route::get('/shifts/board-data', [ShiftController::class, 'boardData'])->name('shifts.board-data');
            Route::get('/shifts/summary', [ShiftController::class, 'summary'])->name('shifts.summary');
            Route::get('/shifts/templates', [ShiftController::class, 'templates'])->name('shifts.templates.index');
            Route::post('/shifts/templates', [ShiftController::class, 'storeTemplate'])->name('shifts.templates.store');
            Route::post('/shifts/templates/{template}/apply', [ShiftController::class, 'applyTemplate'])->name('shifts.templates.apply');
            Route::put('/shifts/templates/{template}', [ShiftController::class, 'updateTemplate'])->name('shifts.templates.update');
            Route::delete('/shifts/templates/{template}', [ShiftController::class, 'destroyTemplate'])->name('shifts.templates.destroy');
            Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
            Route::put('/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
            Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

            // Estações (aba dentro do módulo)
            Route::get('/stations', [StationController::class, 'index'])->name('stations.index');
            Route::post('/stations', [StationController::class, 'store'])->name('stations.store');
            Route::put('/stations/{station}', [StationController::class, 'update'])->name('stations.update');
            Route::delete('/stations/{station}', [StationController::class, 'destroy'])->name('stations.destroy');
            Route::post('/stations/reorder', [StationController::class, 'reorder'])->name('stations.reorder');
        });

        // ── Módulo: Ponto ─────────────────────────────────────────
        Route::middleware('module:time_clock')->group(function () {
            Route::get('/time-entries', [TimeEntryController::class, 'index'])->name('time-entries.index');
            Route::post('/time-entries', [TimeEntryController::class, 'store'])->name('time-entries.store');
            Route::get('/time-entries/my', [TimeEntryController::class, 'personalDashboard'])->name('time-entries.dashboard');
            Route::get('/time-entries/monthly-report', [TimeEntryController::class, 'monthlyReport'])->name('time-entries.monthly-report');
            Route::get('/time-entries/corrections', [TimeEntryController::class, 'corrections'])->name('time-entries.corrections');

            Route::middleware('admin')->group(function () {
                Route::resource('work-schedules', WorkScheduleController::class)->except(['show', 'destroy']);
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

        // ── Log de atividades dedicado (admin+) ─────────────────
        Route::middleware('admin')->group(function () {
            Route::get('/admin/audit-logs', [AuditLogAdminController::class, 'index'])->name('admin.audit-logs.index');
        });

        // ── Superadmin ───────────────────────────────────────────
        Route::middleware('superadmin')->prefix('admin')->name('admin.')->group(function () {
            Route::get('dashboard', [GlobalDashboardController::class, 'index'])->name('dashboard');
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

// Ponto tab on the login page — credential punch, no auth required
Route::post('/clock/credential', [ClockController::class, 'credentialPunch'])->name('clock.credential');

// BUG-07: /inventory → redireciona para /estoque (módulo em desenvolvimento)
Route::get('/inventory', fn() => redirect()->route('estoque.index'))
    ->middleware(['auth', 'active', 'company.active']);

// BUG-08: /superadmin → 403 consistente para não-superadmin (via middleware superadmin)
Route::get('/superadmin', fn() => redirect()->route('admin.dashboard'))
    ->middleware(['auth', 'active', 'superadmin']);

require __DIR__.'/auth.php';