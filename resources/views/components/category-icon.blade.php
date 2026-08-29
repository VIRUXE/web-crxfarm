@props(['category'])

@php
    $class = $attributes->get('class', 'size-4');
@endphp

@switch($category)
    @case(\App\Enums\PartCategory::EngineDrivetrain->value)
        {{-- Engine block with piston --}}
        <svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 10h6V6h4v4h2l3 3v6H4z"></path>
            <path d="M9 10v10"></path>
            <circle cx="17" cy="17" r="1.5"></circle>
        </svg>
        @break

    @case(\App\Enums\PartCategory::ExteriorBody->value)
        {{-- Car silhouette --}}
        <svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 16v-3l2.5-5A2 2 0 0 1 7.3 7h9.4a2 2 0 0 1 1.8 1.1L21 13v3"></path>
            <path d="M3 16h18"></path>
            <path d="M5 13h14"></path>
            <circle cx="7" cy="17.5" r="1.5"></circle>
            <circle cx="17" cy="17.5" r="1.5"></circle>
        </svg>
        @break

    @case(\App\Enums\PartCategory::Interior->value)
        {{-- Seat --}}
        <svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M7 4v8a2 2 0 0 0 2 2h6"></path>
            <path d="M7 12H6a2 2 0 0 0-2 2v2"></path>
            <path d="M15 10h2a2 2 0 0 1 2 2v6a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-3"></path>
        </svg>
        @break

    @case(\App\Enums\PartCategory::LightingElectrical->value)
        {{-- Headlight beam --}}
        <svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4 8a5 5 0 0 1 5-5h1a5 5 0 0 1 5 5v8H4z"></path>
            <path d="M15 10h5"></path>
            <path d="M15 14h4"></path>
            <path d="M15 18h3"></path>
        </svg>
        @break

    @case(\App\Enums\PartCategory::SuspensionBrakes->value)
        {{-- Coilover suspension strut and disc brake --}}
        <svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 2v4"></path>
            <path d="M8 6h8"></path>
            <path d="M9 10h6"></path>
            <path d="M8 14h8"></path>
            <path d="M9 18h6"></path>
            <path d="M12 18v4"></path>
        </svg>
        @break

    @case(\App\Enums\PartCategory::WheelsTires->value)
        {{-- Alloy wheel rim with spokes --}}
        <svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M12 3v6"></path>
            <path d="M12 15v6"></path>
            <path d="M3 12h6"></path>
            <path d="M15 12h6"></path>
            <path d="m5.6 5.6 4.3 4.3"></path>
            <path d="m14.1 14.1 4.3 4.3"></path>
            <path d="m18.4 5.6-4.3 4.3"></path>
            <path d="m9.9 14.1-4.3 4.3"></path>
        </svg>
        @break

    @case(\App\Enums\PartCategory::ExhaustIntake->value)
        {{-- Exhaust pipe --}}
        <svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M3 8h9a4 4 0 0 1 4 4v0"></path>
            <path d="M14 10.5h4a2.5 2.5 0 0 1 0 5h-6"></path>
            <ellipse cx="19.5" cy="13" rx="1.5" ry="2.5"></ellipse>
        </svg>
        @break

    @default
        {{-- Wrench, for Other / Misc --}}
        <svg class="{{ $class }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14.7 6.3a4 4 0 0 0-5.4 4.6L4 16.2V20h3.8l5.3-5.3a4 4 0 0 0 4.6-5.4l-2.5 2.5-2-2z"></path>
        </svg>
@endswitch
