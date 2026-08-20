<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SubscriberController as AdminSubscriberController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\BarcodeLabelController;
use App\Http\Controllers\BarcodePrintController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyInvitationController;
use App\Http\Controllers\CompanyOnboardingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LabelTemplateController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MarketingPageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StockCountController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/features', MarketingPageController::class)->name('marketing.features');
Route::get('/pricing', MarketingPageController::class)->name('marketing.pricing');
Route::get('/barcode-inventory-system', MarketingPageController::class)->name('marketing.barcode-inventory-system');
Route::get('/barcode-label-printing', MarketingPageController::class)->name('marketing.barcode-label-printing');
Route::get('/inventory-management-small-business', MarketingPageController::class)->name('marketing.inventory-management-small-business');
Route::get('/warehouse-inventory-management', MarketingPageController::class)->name('marketing.warehouse-inventory-management');
Route::get('/stock-counting-software', MarketingPageController::class)->name('marketing.stock-counting-software');
Route::get('/inventory-management-saudi-arabia', MarketingPageController::class)->name('marketing.inventory-management-saudi-arabia');
Route::get('/sitemap.xml', [SitemapController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::post('/locale', LocaleController::class)->name('locale.update');

Route::get('/invitations/{invitation:token}', [CompanyInvitationController::class, 'show'])->name('invitations.show');
Route::get('/invitations/{invitation:token}/login', [CompanyInvitationController::class, 'login'])->name('invitations.login');
Route::post('/invitations/{invitation:token}/accept', [CompanyInvitationController::class, 'accept'])
    ->middleware('throttle:10,1')
    ->name('invitations.accept');

Route::get('/dashboard', function () {
    $company = request()->user()->activeCompanies()->first();

    if ($company) {
        return redirect()->route('companies.dashboard', $company);
    }

    return request()->user()->is_platform_admin
        ? redirect()->route('admin.dashboard')
        : redirect()->route('companies.create');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'platform.admin'])->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/subscribers', [AdminSubscriberController::class, 'index'])->name('subscribers.index');
    Route::get('/subscribers/{company:slug}', [AdminSubscriberController::class, 'show'])->name('subscribers.show');
    Route::patch('/subscribers/{company:slug}/limits', [AdminSubscriberController::class, 'updateLimits'])->name('subscribers.limits.update');
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/tickets', [AdminSupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [AdminSupportTicketController::class, 'show'])->name('tickets.show');
    Route::patch('/tickets/{ticket}', [AdminSupportTicketController::class, 'update'])->name('tickets.update');
    Route::post('/tickets/{ticket}/replies', [AdminSupportTicketController::class, 'reply'])->name('tickets.replies.store');
});

Route::prefix('app/{company:slug}')->name('companies.')->middleware(['auth', 'company.member'])->group(function () {
    Route::middleware('company.permission:people.manage')->group(function () {
        Route::get('/onboarding', [CompanyOnboardingController::class, 'edit'])->name('onboarding.edit');
        Route::patch('/onboarding', [CompanyOnboardingController::class, 'update'])->name('onboarding.update');
    });
    Route::get('/dashboard', DashboardController::class)->middleware('company.permission:dashboard.view')->name('dashboard');

    Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/replies', [SupportTicketController::class, 'reply'])->name('tickets.replies.store');

    Route::middleware('company.permission:warehouses.manage')->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::get('/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
        Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
    });

    Route::middleware('company.permission:products.manage')->group(function () {
        Route::get('/products/import/template', [ProductImportController::class, 'template'])->name('products.import.template');
        Route::post('/products/import', [ProductImportController::class, 'store'])->name('products.import.store');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}', [ProductController::class, 'update']);
    });
    Route::middleware('company.permission:products.view')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    });

    Route::get('/movements', [StockMovementController::class, 'index'])->middleware('company.permission:inventory.view')->name('movements.index');
    Route::post('/movements', [StockMovementController::class, 'store'])->middleware('company.permission:inventory.move')->name('movements.store');
    Route::get('/stock-counts', [StockCountController::class, 'index'])->middleware('company.permission:inventory.view')->name('stock-counts.index');
    Route::get('/stock-counts/{stockCount}', [StockCountController::class, 'show'])->middleware('company.permission:inventory.view')->name('stock-counts.show');
    Route::middleware('company.permission:inventory.count')->group(function () {
        Route::post('/stock-counts', [StockCountController::class, 'store'])->name('stock-counts.store');
        Route::patch('/stock-counts/{stockCount}/lines/{line}', [StockCountController::class, 'updateLine'])->name('stock-counts.lines.update');
        Route::post('/stock-counts/{stockCount}/finalize', [StockCountController::class, 'finalize'])->name('stock-counts.finalize');
    });

    Route::middleware('company.permission:labels.print')->group(function () {
        Route::get('/products/{product}/label', [BarcodeLabelController::class, 'show'])->name('products.label');
        Route::get('/print-labels', [BarcodePrintController::class, 'index'])->name('labels.index');
        Route::get('/print-labels/preview', [BarcodePrintController::class, 'preview'])->name('labels.preview');
    });
    Route::middleware('company.permission:labels.manage')->group(function () {
        Route::get('/label-templates', [LabelTemplateController::class, 'index'])->name('label-templates.index');
        Route::get('/label-templates/barcode-preview', [LabelTemplateController::class, 'barcodePreview'])->name('label-templates.barcode-preview');
        Route::post('/label-templates', [LabelTemplateController::class, 'store'])->name('label-templates.store');
        Route::put('/label-templates/{labelTemplate}', [LabelTemplateController::class, 'update'])->name('label-templates.update');
        Route::post('/label-templates/{labelTemplate}/duplicate', [LabelTemplateController::class, 'duplicate'])->name('label-templates.duplicate');
        Route::delete('/label-templates/{labelTemplate}', [LabelTemplateController::class, 'destroy'])->name('label-templates.destroy');
    });

    Route::middleware('company.permission:people.manage')->group(function () {
        Route::get('/people', [PeopleController::class, 'index'])->name('people.index');
        Route::post('/people/users', [PeopleController::class, 'storeMember'])->name('people.members.store');
        Route::put('/people/users/{user}', [PeopleController::class, 'updateMember'])->name('people.members.update');
        Route::delete('/people/users/{user}', [PeopleController::class, 'destroyMember'])->name('people.members.destroy');
        Route::post('/people/teams', [PeopleController::class, 'storeTeam'])->name('people.teams.store');
        Route::put('/people/teams/{team}', [PeopleController::class, 'updateTeam'])->name('people.teams.update');
        Route::delete('/people/teams/{team}', [PeopleController::class, 'destroyTeam'])->name('people.teams.destroy');
    });
});

require __DIR__.'/auth.php';
