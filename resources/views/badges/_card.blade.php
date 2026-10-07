{{-- Badge HTML (aperçu écran et impression). $card provient de BadgeService::cardData() --}}
<div class="badge-card">
    <div class="badge-head">
        <div class="badge-brand"><span class="badge-logo">ODC</span><span>Orange<br>Digital Center</span></div>
        <div class="badge-aws"><strong>aws</strong><span>re/Start</span></div>
    </div>
    <div class="badge-title">BADGE PARTICIPANT</div>
    <div class="badge-photo">
        @if ($card['photo'])<img src="{{ $card['photo'] }}" alt="">@else<span>{{ $card['initials'] }}</span>@endif
    </div>
    <div class="badge-name">{{ $card['first_name'] }}<br><b>{{ mb_strtoupper($card['last_name']) }}</b></div>
    <div class="badge-function">{{ $card['function'] ?: 'Participant' }}</div>
    <img class="badge-qr" src="{{ $card['qr'] }}" alt="QR Code">
    <div class="badge-number">{{ $card['number'] }}</div>
    <div class="badge-foot">
        <span>{{ $card['cohort'] }}</span>
        @if ($card['validity'])<small>Valide : {{ $card['validity'] }}</small>@endif
    </div>
</div>
