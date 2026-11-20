<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\Gallery;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDashboardController extends Controller
{
  public function index(): View
  {
    $stats = [
      'total_revenue' => Reservation::where('payment_status', 'settled')->sum('total_price'),
      'settled_bookings' => Reservation::where('payment_status', 'settled')->count(),
      'pending_bookings' => Reservation::where('payment_status', 'pending')->count(),
      'total_plots' => CampingArea::count(),
    ];

    $campingAreas = CampingArea::orderBy('id')->get();
    $facilities = Facility::orderBy('category')->get();
    $galleries = Gallery::latest()->get();
    $reservations = Reservation::with(['user', 'campingArea', 'items.facility', 'ticket'])
      ->latest()
      ->paginate(15);

    return view('admin.dashboard', compact(
      'stats',
      'campingAreas',
      'facilities',
      'galleries',
      'reservations'
    ));
  }

  public function storeCampingArea(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:120'],
      'capacity' => ['required', 'integer', 'min:1'],
      'price_per_night' => ['required', 'numeric', 'min:0'],
      'status' => ['required', 'in:available,maintenance'],
      'description' => ['nullable', 'string'],
      'x' => ['required', 'integer', 'min:0', 'max:800'],
      'y' => ['required', 'integer', 'min:0', 'max:500'],
      'width' => ['required', 'integer', 'min:30', 'max:200'],
      'height' => ['required', 'integer', 'min:30', 'max:200'],
      'zone' => ['required', 'string', 'max:80'],
    ]);

    CampingArea::create([
      'name' => $validated['name'],
      'capacity' => $validated['capacity'],
      'price_per_night' => $validated['price_per_night'],
      'status' => $validated['status'],
      'description' => $validated['description'],
      'layout_coordinates' => [
        'x' => (int) $validated['x'],
        'y' => (int) $validated['y'],
        'width' => (int) $validated['width'],
        'height' => (int) $validated['height'],
        'zone' => $validated['zone'],
      ],
    ]);

    return back()->with('success', 'Camping plot added.');
  }

  public function updateCampingArea(Request $request, CampingArea $campingArea): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:120'],
      'capacity' => ['required', 'integer', 'min:1'],
      'price_per_night' => ['required', 'numeric', 'min:0'],
      'status' => ['required', 'in:available,maintenance'],
      'x' => ['required', 'integer', 'min:0', 'max:800'],
      'y' => ['required', 'integer', 'min:0', 'max:500'],
      'zone' => ['required', 'string', 'max:80'],
    ]);

    $coords = $campingArea->layout_coordinates ?? [];
    $coords['x'] = (int) $validated['x'];
    $coords['y'] = (int) $validated['y'];
    $coords['zone'] = $validated['zone'];

    $campingArea->update([
      'name' => $validated['name'],
      'capacity' => $validated['capacity'],
      'price_per_night' => $validated['price_per_night'],
      'status' => $validated['status'],
      'layout_coordinates' => $coords,
    ]);

    return back()->with('success', "Plot {$campingArea->name} updated.");
  }

  public function storeFacility(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:120'],
      'category' => ['required', 'string', 'max:50'],
      'stock' => ['required', 'integer', 'min:0'],
      'rental_price' => ['required', 'numeric', 'min:0'],
      'unit' => ['required', 'string', 'max:30'],
    ]);

    Facility::create($validated);

    return back()->with('success', 'Equipment item added.');
  }

  public function updateFacility(Request $request, Facility $facility): RedirectResponse
  {
    $validated = $request->validate([
      'stock' => ['required', 'integer', 'min:0'],
      'rental_price' => ['required', 'numeric', 'min:0'],
    ]);

    $facility->update($validated);

    return back()->with('success', "Updated {$facility->name}.");
  }

  public function storeGallery(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'title' => ['required', 'string', 'max:150'],
      'category' => ['required', 'in:hero,camping_area,ambiance,facilities'],
      'image_url' => ['required', 'url'],
      'thumbnail_url' => ['required', 'url'],
      'photographer_name' => ['required', 'string', 'max:100'],
      'source_provider' => ['required', 'in:pexels,pixabay,unsplash'],
      'is_featured' => ['nullable', 'boolean'],
    ]);

    Gallery::create([
      ...$validated,
      'is_featured' => (bool) ($validated['is_featured'] ?? false),
    ]);

    return back()->with('success', 'Gallery photo added.');
  }

  public function destroyGallery(Gallery $gallery): RedirectResponse
  {
    $gallery->delete();
    return back()->with('success', 'Gallery photo removed.');
  }

  public function exportCsv(): StreamedResponse
  {
    $headers = [
      'Content-Type' => 'text/csv',
      'Content-Disposition' => 'attachment; filename="bukit-embun-transactions-' . now()->format('Ymd') . '.csv"',
    ];

    return response()->stream(function () {
      $handle = fopen('php://output', 'w');
      fputcsv($handle, ['Booking Code', 'Visitor', 'Email', 'Plot', 'Check-In', 'Check-Out', 'Guests', 'Total Price', 'Status', 'Paid At']);

      Reservation::with(['user', 'campingArea'])->orderBy('id')->chunk(200, function ($rows) use ($handle) {
        foreach ($rows as $row) {
          fputcsv($handle, [
            $row->booking_code,
            $row->user->name,
            $row->user->email,
            $row->campingArea->name,
            $row->check_in_date->toDateString(),
            $row->check_out_date->toDateString(),
            $row->guest_count,
            $row->total_price,
            $row->payment_status,
            $row->paid_at?->toDateTimeString() ?? '-',
          ]);
        }
      });

      fclose($handle);
    }, 200, $headers);
  }
}
