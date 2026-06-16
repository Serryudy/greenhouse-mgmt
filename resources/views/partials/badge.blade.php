@php
    $map = [
        'normal'       => ['badge-normal', 'Normal'],
        'warning'      => ['badge-warning', 'Warning'],
        'critical'     => ['badge-critical', 'Critical'],
        'active'       => ['badge-critical', 'Active'],
        'acknowledged' => ['badge-acknowledged', 'Acknowledged'],
        'resolved'     => ['badge-resolved', 'Resolved'],
        'unknown'      => ['badge-neutral', 'Unknown'],
        'pending'      => ['badge-neutral', 'Pending'],
        'sent'         => ['badge-acknowledged', 'Sent'],
        'failed'       => ['badge-critical', 'Failed'],
    ];
    [$cls, $defLabel] = $map[$status] ?? ['badge-neutral', ucfirst($status)];
    $label = $label ?? $defLabel;
@endphp
<span class="badge-status {{ $cls }}"><span class="dot"></span>{{ $label }}</span>
