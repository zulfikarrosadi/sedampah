<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Petugas\ScannerController;
use App\Http\Controllers\Visitor\CampingController;
use Illuminate\Support\Facades\Route;

// Public Visitor Landing & Gallery
Route::get('/', [CampingController::class, 'index'])->name('home');
Route::get('/booking', [CampingController::class, 'bookingPage'])->name('booking.index');
Route::get('/api/availability', [CampingController::class, 'checkAvailabilityApi'])->name('api.availability');

// Authenticated Visitor Actions
Route::middleware(['auth'])->group(function () {
  Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    return match ($request->user()->role) {
      'admin' => redirect()->route('admin.dashboard'),
      'petugas' => redirect()->route('petugas.scan'),
      default => redirect()->route('visitor.tickets'),
    };
  })->name('dashboard');

  Route::post('/booking/checkout', [CampingController::class, 'checkout'])->name('booking.checkout');
  Route::get('/my-tickets', [CampingController::class, 'myTickets'])->name('visitor.tickets');
  Route::get('/tickets/{ticket}', [CampingController::class, 'showTicket'])->name('tickets.show');
});

// Staff (Petugas) Scanner Portal
Route::middleware(['auth', 'role:petugas,admin'])->prefix('petugas')->name('petugas.')->group(function () {
  Route::get('/scan', [ScannerController::class, 'index'])->name('scan');
});

// Admin Control Panel
// Admin Control Panel
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
  Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

  // Camping Plots CRUD
  Route::post('/areas', [AdminDashboardController::class, 'storeCampingArea'])->name('areas.store');
  Route::patch('/areas/{campingArea}', [AdminDashboardController::class, 'updateCampingArea'])->name('areas.update');
  Route::delete('/areas/{campingArea}', [AdminDashboardController::class, 'destroyCampingArea'])->name('areas.destroy');

  // Equipment / Facilities CRUD
  Route::post('/facilities', [AdminDashboardController::class, 'storeFacility'])->name('facilities.store');
  Route::patch('/facilities/{facility}', [AdminDashboardController::class, 'updateFacility'])->name('facilities.update');
  Route::delete('/facilities/{facility}', [AdminDashboardController::class, 'destroyFacility'])->name('facilities.destroy');

  // Gallery Assets CRUD (Local File Upload + External URL)
  Route::post('/galleries', [AdminDashboardController::class, 'storeGallery'])->name('galleries.store');
  Route::patch('/galleries/{gallery}/toggle-featured', [AdminDashboardController::class, 'toggleGalleryFeatured'])->name('galleries.toggle-featured');
  Route::delete('/galleries/{gallery}', [AdminDashboardController::class, 'destroyGallery'])->name('galleries.destroy');

  // Reports
  Route::get('/export-csv', [AdminDashboardController::class, 'exportCsv'])->name('export.csv');
});

require __DIR__ . '/auth.php';
