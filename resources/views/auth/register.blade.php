@extends('layouts.nature')

@section('title', 'Buat Akun — Bukit Embun')

@section('content')
<div class="min-h-[82vh] flex items-center justify-center px-4 py-12 bg-gradient-to-b from-emerald-950/5 to-stone-50">
  <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-stone-200/80 p-8">
    <div class="text-center mb-6">
      <span class="inline-flex w-12 h-12 rounded-2xl bg-forest-900 text-amber-200 items-center justify-center font-serif font-bold text-xl shadow">BE</span>
      <h1 class="font-serif text-2xl font-bold text-forest-900 mt-3">Buat Akun Pengunjung</h1>
      <p class="text-xs text-stone-500 mt-1">Daftar untuk memesan area perkemahan dan mendapatkan tiket masuk dengan kode QR.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
      @csrf

      <div>
        <label for="name" class="block text-xs font-bold uppercase text-stone-600 mb-1">Nama Lengkap</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
        @error('name')
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="email" class="block text-xs font-bold uppercase text-stone-600 mb-1">Alamat Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
        @error('email')
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="phone" class="block text-xs font-bold uppercase text-stone-600 mb-1">Nomor Telepon / WhatsApp</label>
        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="081234567890" required autocomplete="tel"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
        @error('phone')
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="password" class="block text-xs font-bold uppercase text-stone-600 mb-1">Kata Sandi</label>
        <input id="password" type="password" name="password" required autocomplete="new-password"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
        @error('password')
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label for="password_confirmation" class="block text-xs font-bold uppercase text-stone-600 mb-1">Konfirmasi Kata Sandi</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
      </div>

      <button type="submit"
        class="w-full py-3 rounded-xl bg-terracotta-600 hover:bg-terracotta-700 text-white font-bold text-sm shadow-lg transition cursor-pointer">
        Daftar &amp; Jelajahi Peta &rarr;
      </button>
    </form>

    <p class="text-center text-xs text-stone-500 mt-6 pt-5 border-t border-stone-100">
      Sudah memiliki akun?
      <a href="{{ route('login') }}" class="font-bold text-forest-900 hover:text-terracotta-600 hover:underline">
        Masuk
      </a>
    </p>
  </div>
</div>
@endsection