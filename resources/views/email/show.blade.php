@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><h3>Send Email</h3></div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('email.send') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="row">
                            <div class="col-md-6">
                            <div class="form-group row">
                                <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('Email From') }}</label>

                                <div class="col-md-6">
                                    <input id="email" type="email" class="form-control @error('sender') is-invalid @enderror" name="sender" value="{{ $sender->email }}" required readonly>

                                    @error('sender')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="emails" class="col-md-4 col-form-label text-md-right">{{ __('Email To') }}</label>

                                <div class="col-md-6">
                                    <select name="recipient[]" multiple id="emails" class="form-control multi-select" required>
{{--                                        <option value="all">All</option>--}}
                                        @foreach($users as $user)
                                            <option value="{{ $user->email }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="subject" class="col-md-4 col-form-label text-md-right">{{ __('Email Subject') }}</label>

                                <div class="col-md-6">
                                    <input id="subject" type="text" class="form-control @error('subject') is-invalid @enderror" name="subject" value="{{ old('subject') }}" required autocomplete="subject" autofocus>

                                    @error('subject')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="content" class="col-md-4 col-form-label text-md-right">{{ __('Email Body') }}</label>

                                <div class="col-md-6">
                                    <textarea name="content" id="content" cols="30" rows="10" class="form-control @error('content') is-invalid @enderror">{{ old('content') }}</textarea>

                                    @error('content')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group row">
                                    <label for="emails_cc" class="col-md-4 col-form-label text-md-right">{{ __('Email CC') }}</label>

                                    <div class="col-md-6">
                                        <select name="cc[]" multiple id="emails_cc" class="form-control multi-select">
                                            <option value="">Select CC</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->email }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="emails_bcc" class="col-md-4 col-form-label text-md-right">{{ __('Email BCC') }}</label>

                                    <div class="col-md-6">
                                        <select name="bcc[]" multiple id="emails_bcc" class="form-control multi-select">
                                            <option value="">Select BCC</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->email }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="emails_attachments" class="col-md-4 col-form-label text-md-right">{{ __('Email Attachments') }}</label>

                                    <div class="col-md-6">
                                        <input type="file" name="attachments[]" multiple id="emails_attachments"
                                               class="form-control @error('attachments') is-invalid @enderror">
                                        @error('attachments')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-12 text-left">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Send Email') }}
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
