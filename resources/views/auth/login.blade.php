@extends('layouts.nature')

@section('title', 'Masuk — Bukit Embun')

@section('content')
<div class="min-h-[78vh] flex items-center justify-center px-4 py-12 bg-gradient-to-b from-emerald-950/5 to-stone-50">
  <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-stone-200/80 p-8">
    <div class="text-center mb-6">
      <span class="inline-flex w-12 h-12 rounded-2xl bg-forest-900 text-amber-200 items-center justify-center font-serif font-bold text-xl shadow">BE</span>
      <h1 class="font-serif text-2xl font-bold text-forest-900 mt-3">Selamat Datang Kembali</h1>
      <p class="text-xs text-stone-500 mt-1">Masuk untuk mengelola pemesanan kapling kemah dan tiket QR Anda.</p>
    </div>

    @if (session('status'))
    <div class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 font-medium">
      {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
      @csrf

      <div>
        <label for="email" class="block text-xs font-bold uppercase text-stone-600 mb-1">Alamat Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
        @error('email')
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <div class="flex justify-between items-center mb-1">
          <label for="password" class="block text-xs font-bold uppercase text-stone-600">Kata Sandi</label>
          @if (Route::has('password.request'))
          <a href="{{ route('password.request') }}" class="text-xs text-forest-800 hover:underline">Lupa kata sandi?</a>
          @endif
        </div>
        <input id="password" type="password" name="password" required autocomplete="current-password"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
        @error('password')
        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <label class="inline-flex items-center gap-2 text-xs text-stone-600 cursor-pointer">
        <input type="checkbox" name="remember" class="rounded border-stone-300 text-forest-900 focus:ring-forest-800">
        <span>Ingat saya</span>
      </label>

      <button type="submit"
        class="w-full py-3 rounded-xl bg-terracotta-600 hover:bg-terracotta-700 text-white font-bold text-sm shadow-lg transition cursor-pointer">
        Masuk &rarr;
      </button>
    </form>

    @if (Route::has('register'))
    <p class="text-center text-xs text-stone-500 mt-6 pt-5 border-t border-stone-100">
      Baru di Bukit Embun?
      <a href="{{ route('register') }}" class="font-bold text-forest-900 hover:text-terracotta-600 hover:underline">
        Buat Akun Pengunjung
      </a>
    </p>
    @endif
  </div>
</div>
@endsection