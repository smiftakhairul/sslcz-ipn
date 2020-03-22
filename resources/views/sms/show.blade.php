@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 justify-content-md-center">
                <div class="card">
                    <div class="card-header"><h3>Send SMS</h3></div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('sms.send') }}">
                            @csrf

                            <div class="form-group row">
                                <label for="emails" class="col-md-4 col-form-label text-md-right">{{ __('Recipient') }}</label>

                                <div class="col-md-6">
                                    <select name="recipient[]" multiple id="emails" class="form-control multi-select" required>
                                        {{--                                        <option value="all">All</option>--}}
                                        @foreach($users as $phone => $user)
                                            <option value="{{ $phone }}">{{ $user }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="content" class="col-md-4 col-form-label text-md-right">{{ __('Message') }}</label>

                                <div class="col-md-6">
                                    <textarea name="content" id="content" cols="30" rows="10" class="form-control @error('content') is-invalid @enderror">{{ old('content') }}</textarea>

                                    @error('content')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Send SMS') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
