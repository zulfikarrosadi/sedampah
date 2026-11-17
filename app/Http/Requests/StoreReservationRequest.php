<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreReservationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return Auth::check();
  }

  public function rules(): array
  {
    return [
      'camping_area_id' => ['required', 'integer', 'exists:camping_areas,id'],
      'check_in_date' => ['required', 'date', 'after_or_equal:today'],
      'check_out_date' => ['required', 'date', 'after:check_in_date'],
      'guest_count' => ['required', 'integer', 'min:1', 'max:50'],
      'items' => ['nullable', 'array'],
      'items.*.facility_id' => ['required_with:items', 'integer', 'exists:facilities,id'],
      'items.*.qty' => ['required_with:items', 'integer', 'min:1'],
    ];
  }

  public function messages(): array
  {
    return [
      'check_in_date.after_or_equal' => 'Tanggal kedatangan tidak boleh sebelum hari ini.',
      'check_out_date.after' => 'Tanggal kepulangan minimal 1 malam setelah tanggal kedatangan.',
    ];
  }
}
