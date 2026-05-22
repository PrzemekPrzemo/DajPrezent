@extends('layouts.public')

@section('title', 'Lista chroniona hasłem')

@section('content')
    <div style="max-width:420px;margin:3rem auto 0;">
        <div class="card">
            <h1>{{ $tenant->name }}</h1>
            <p style="color:#6b7280;">Ta lista jest chroniona hasłem. Podaj hasło, które otrzymałeś od właściciela.</p>

            @if ($errors->any())
                <div role="alert" style="background:#fee2e2;color:#991b1b;padding:.5rem .75rem;border-radius:.5rem;margin-bottom:1rem;">
                    {{ $errors->first('password') }}
                </div>
            @endif

            <form method="POST" action="/{{ $tenant->slug }}/unlock" style="text-align:left;">
                @csrf
                <label for="password" style="display:block;font-weight:600;margin-bottom:.25rem;">Hasło</label>
                <input id="password" type="password" name="password" required autofocus maxlength="128" style="width:100%;padding:.5rem .75rem;border:1px solid #d1d5db;border-radius:.375rem;font-size:1rem;">
                <button type="submit" style="margin-top:.75rem;width:100%;background:#ec4899;color:#fff;border:0;padding:.6rem 1rem;border-radius:.5rem;font-weight:600;cursor:pointer;">
                    Odblokuj listę
                </button>
            </form>
        </div>
    </div>
@endsection
