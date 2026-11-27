<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\Gallery;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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

    $campingAreas = CampingArea::withCount(['reservations', 'reservationAreas'])
      ->orderBy('id')
      ->get();

    $facilities = Facility::withCount('reservationItems')
      ->orderBy('category')
      ->orderBy('name')
      ->get();

    $galleries = Gallery::orderByDesc('is_featured')
      ->latest()
      ->get();

    $reservations = Reservation::with([
      'user',
      'campingArea',
      'areas.campingArea',
      'items.facility',
      'ticket',
    ])
      ->latest()
      ->paginate(15);

    $users = User::withCount('reservations')
      ->orderByRaw("FIELD(role, 'admin', 'petugas', 'pengunjung')")
      ->latest()
      ->get();

    return view('admin.dashboard', compact(
      'stats',
      'campingAreas',
      'facilities',
      'galleries',
      'reservations',
      'users'
    ));
  }

  public function storeUser(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
      'phone' => ['nullable', 'string', 'max:20'],
      'role' => ['required', Rule::in(['admin', 'petugas', 'pengunjung'])],
      'password' => ['required', Password::defaults()],
    ]);

    User::create([
      'name' => $validated['name'],
      'email' => $validated['email'],
      'phone' => $validated['phone'] ?? null,
      'role' => $validated['role'],
      'password' => Hash::make($validated['password']),
      'email_verified_at' => now(),
    ]);

    return back()->with('success', "User '{$validated['name']}' ({$validated['role']}) created.");
  }

  public function updateUser(Request $request, User $user): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
      'phone' => ['nullable', 'string', 'max:20'],
      'role' => ['required', Rule::in(['admin', 'petugas', 'pengunjung'])],
      'password' => ['nullable', Password::defaults()],
    ]);

    // Prevent the currently logged-in admin from demoting themselves or locking out the last admin
    if ($user->id === $request->user()->id && $validated['role'] !== 'admin') {
      return back()->with('error', 'You cannot demote your own administrator account.');
    }

    if ($user->role === 'admin' && $validated['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
      return back()->with('error', 'Cannot change role: at least one administrator must remain.');
    }

    $updateData = [
      'name' => $validated['name'],
      'email' => $validated['email'],
      'phone' => $validated['phone'] ?? null,
      'role' => $validated['role'],
    ];

    if (!empty($validated['password'])) {
      $updateData['password'] = Hash::make($validated['password']);
    }

    $user->update($updateData);

    return back()->with('success', "Updated user '{$user->name}'.");
  }

  public function destroyUser(Request $request, User $user): RedirectResponse
  {
    if ($user->id === $request->user()->id) {
      return back()->with('error', 'You cannot delete your own currently active account.');
    }

    if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
      return back()->with('error', 'Cannot delete the last remaining administrator account.');
    }

    if ($user->reservations()->exists()) {
      return back()->with('error', "Cannot delete '{$user->name}' because they have existing reservation history.");
    }

    $name = $user->name;
    $user->delete();

    return back()->with('success', "User '{$name}' deleted.");
  }

  public function storeCampingArea(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:120', 'unique:camping_areas,name'],
      'capacity' => ['required', 'integer', 'min:1', 'max:50'],
      'price_per_night' => ['required', 'numeric', 'min:0'],
      'status' => ['required', 'in:available,maintenance'],
      'description' => ['nullable', 'string', 'max:1000'],
      'x' => ['required', 'integer', 'min:0', 'max:1140'],
      'y' => ['required', 'integer', 'min:0', 'max:740'],
      'width' => ['required', 'integer', 'min:40', 'max:300'],
      'height' => ['required', 'integer', 'min:35', 'max:200'],
      'zone' => ['required', 'string', 'max:80'],
      'elevation' => ['nullable', 'string', 'max:40'],
    ]);

    CampingArea::create([
      'name' => $validated['name'],
      'capacity' => (int) $validated['capacity'],
      'price_per_night' => $validated['price_per_night'],
      'status' => $validated['status'],
      'description' => $validated['description'] ?? null,
      'layout_coordinates' => [
        'x' => (int) $validated['x'],
        'y' => (int) $validated['y'],
        'width' => (int) $validated['width'],
        'height' => (int) $validated['height'],
        'zone' => $validated['zone'],
        'elevation' => $validated['elevation'] ?: '1,400 mdpl',
      ],
    ]);

    return back()->with('success', "Camping plot '{$validated['name']}' created.");
  }

  public function updateCampingArea(Request $request, CampingArea $campingArea): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:120', 'unique:camping_areas,name,' . $campingArea->id],
      'capacity' => ['required', 'integer', 'min:1', 'max:50'],
      'price_per_night' => ['required', 'numeric', 'min:0'],
      'status' => ['required', 'in:available,maintenance'],
      'description' => ['nullable', 'string', 'max:1000'],
      'x' => ['required', 'integer', 'min:0', 'max:1140'],
      'y' => ['required', 'integer', 'min:0', 'max:740'],
      'width' => ['nullable', 'integer', 'min:40', 'max:300'],
      'height' => ['nullable', 'integer', 'min:35', 'max:200'],
      'zone' => ['required', 'string', 'max:80'],
      'elevation' => ['nullable', 'string', 'max:40'],
    ]);

    $coords = $campingArea->layout_coordinates ?? [];
    $coords['x'] = (int) $validated['x'];
    $coords['y'] = (int) $validated['y'];
    $coords['width'] = (int) ($validated['width'] ?? $coords['width'] ?? 100);
    $coords['height'] = (int) ($validated['height'] ?? $coords['height'] ?? 64);
    $coords['zone'] = $validated['zone'];
    if (!empty($validated['elevation'])) {
      $coords['elevation'] = $validated['elevation'];
    }

    $campingArea->update([
      'name' => $validated['name'],
      'capacity' => (int) $validated['capacity'],
      'price_per_night' => $validated['price_per_night'],
      'status' => $validated['status'],
      'description' => $validated['description'] ?? $campingArea->description,
      'layout_coordinates' => $coords,
    ]);

    return back()->with('success', "Plot '{$campingArea->name}' updated.");
  }

  public function destroyCampingArea(CampingArea $campingArea): RedirectResponse
  {
    $hasBookings = $campingArea->reservations()->exists()
      || $campingArea->reservationAreas()->exists();

    if ($hasBookings) {
      return back()->with('error', "Cannot delete '{$campingArea->name}' because it has existing reservation history. Set its status to 'maintenance' instead.");
    }

    $name = $campingArea->name;
    $campingArea->delete();

    return back()->with('success', "Camping plot '{$name}' deleted.");
  }

  public function storeFacility(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:120', 'unique:facilities,name'],
      'category' => ['required', 'string', 'max:50'],
      'stock' => ['required', 'integer', 'min:0', 'max:1000'],
      'rental_price' => ['required', 'numeric', 'min:0'],
      'unit' => ['required', 'string', 'max:30'],
    ]);

    Facility::create($validated);

    return back()->with('success', "Equipment '{$validated['name']}' added.");
  }

  public function updateFacility(Request $request, Facility $facility): RedirectResponse
  {
    $validated = $request->validate([
      'name' => ['sometimes', 'required', 'string', 'max:120', 'unique:facilities,name,' . $facility->id],
      'category' => ['sometimes', 'required', 'string', 'max:50'],
      'stock' => ['required', 'integer', 'min:0', 'max:1000'],
      'rental_price' => ['required', 'numeric', 'min:0'],
      'unit' => ['sometimes', 'required', 'string', 'max:30'],
    ]);

    $facility->update($validated);

    return back()->with('success', "Updated '{$facility->name}'.");
  }

  public function destroyFacility(Facility $facility): RedirectResponse
  {
    if ($facility->reservationItems()->exists()) {
      return back()->with('error', "Cannot delete '{$facility->name}' because it is linked to historical reservations. Set stock to 0 instead.");
    }

    $name = $facility->name;
    $facility->delete();

    return back()->with('success', "Equipment '{$name}' deleted.");
  }

  public function storeGallery(Request $request): RedirectResponse
  {
    $validated = $request->validate([
      'title' => ['required', 'string', 'max:150'],
      'category' => ['required', 'in:hero,camping_area,ambiance,facilities'],
      'upload_mode' => ['required', 'in:file,url'],
      'image_file' => ['required_if:upload_mode,file', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
      'image_url' => ['required_if:upload_mode,url', 'nullable', 'url', 'max:500'],
      'thumbnail_url' => ['nullable', 'url', 'max:500'],
      'photographer_name' => ['nullable', 'string', 'max:100'],
      'source_provider' => ['nullable', 'in:pexels,pixabay,unsplash,local'],
      'is_featured' => ['nullable', 'boolean'],
    ]);

    if ($validated['upload_mode'] === 'file' && $request->hasFile('image_file')) {
      $path = $request->file('image_file')->store('galleries', 'public');
      $publicUrl = Storage::url($path);

      Gallery::create([
        'title' => $validated['title'],
        'category' => $validated['category'],
        'image_url' => $publicUrl,
        'thumbnail_url' => $publicUrl,
        'photographer_name' => $validated['photographer_name'] ?: 'Bukit Embun Official',
        'source_provider' => 'local',
        'external_id' => $path,
        'is_featured' => (bool) ($validated['is_featured'] ?? false),
      ]);
    } else {
      Gallery::create([
        'title' => $validated['title'],
        'category' => $validated['category'],
        'image_url' => $validated['image_url'],
        'thumbnail_url' => $validated['thumbnail_url'] ?: $validated['image_url'],
        'photographer_name' => $validated['photographer_name'] ?: 'Curated Stock',
        'source_provider' => $validated['source_provider'] ?? 'unsplash',
        'external_id' => 'ext-' . Str::random(10),
        'is_featured' => (bool) ($validated['is_featured'] ?? false),
      ]);
    }

    return back()->with('success', "Gallery asset '{$validated['title']}' published.");
  }

  public function toggleGalleryFeatured(Gallery $gallery): RedirectResponse
  {
    $gallery->update(['is_featured' => !$gallery->is_featured]);

    return back()->with('success', "Updated featured status for '{$gallery->title}'.");
  }

  public function destroyGallery(Gallery $gallery): RedirectResponse
  {
    if ($gallery->source_provider === 'local' && !empty($gallery->external_id)) {
      Storage::disk('public')->delete($gallery->external_id);
    }

    $title = $gallery->title;
    $gallery->delete();

    return back()->with('success', "Gallery asset '{$title}' deleted.");
  }

  public function exportCsv(): StreamedResponse
  {
    $headers = [
      'Content-Type' => 'text/csv',
      'Content-Disposition' => 'attachment; filename="bukit-embun-transactions-' . now()->format('Ymd') . '.csv"',
    ];

    return response()->stream(function () {
      $handle = fopen('php://output', 'w');
      fputcsv($handle, ['Booking Code', 'Visitor', 'Email', 'Plots', 'Check-In', 'Check-Out', 'Guests', 'Total Price', 'Status', 'Paid At']);

      Reservation::with(['user', 'campingArea', 'areas.campingArea'])
        ->orderBy('id')
        ->chunk(200, function ($rows) use ($handle) {
          foreach ($rows as $row) {
            fputcsv($handle, [
              $row->booking_code,
              $row->user->name,
              $row->user->email,
              $row->area_names,
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
