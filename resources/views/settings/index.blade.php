@extends('layouts.admin')

@section('title', 'Settings')
@section('page_title', 'Settings')
@section('breadcrumb', 'Home / Settings')

@section('content')
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        @php
            $icons = [
                'company' => 'fa-building',
                'attendance' => 'fa-clock',
                'leave' => 'fa-plane-departure',
                'general' => 'fa-cog',
                'system' => 'fa-server',
                'notifications' => 'fa-bell',
                'device' => 'fa-fingerprint',
            ];
        @endphp

        @foreach($settings as $group => $items)
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas {{ $icons[$group] ?? 'fa-sliders-h' }} me-2 text-primary"></i>{{ ucfirst($group) }} Settings
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($items as $setting)
                            <div class="col-md-6 col-lg-4">
                                <label for="{{ $setting->key }}" class="form-label">{{ $setting->label ?? ucwords(str_replace('_', ' ', $setting->key)) }}</label>
                                @if($setting->type === 'time')
                                    <input type="time" class="form-control" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ $setting->value }}">
                                @elseif($setting->type === 'number')
                                    <input type="number" class="form-control" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ $setting->value }}">
                                @elseif($setting->type === 'boolean')
                                    <select class="form-select" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                        <option value="1" {{ $setting->value == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ $setting->value == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                @elseif($setting->type === 'select')
                                    <select class="form-select" id="{{ $setting->key }}" name="{{ $setting->key }}">
                                        @foreach(explode(',', $setting->options ?? '') as $option)
                                            @php $option = trim($option); @endphp
                                            <option value="{{ $option }}" {{ $setting->value == $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control" id="{{ $setting->key }}" name="{{ $setting->key }}" value="{{ $setting->value }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Save Settings
            </button>
        </div>
    </form>
@endsection
