@php
    // Gabarit dessiné pour l'A6 (105 × 148 mm) puis mis à l'échelle par $k selon la taille choisie.
    $mm = fn (float $v) => round($v * $k, 2).'mm';
    $pt = fn (float $v) => round($v * $k, 2).'pt';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #0B1426; }
    .page { position: relative; width: {{ $width }}mm; height: {{ $height }}mm; overflow: hidden; page-break-after: always; }
    .page.last { page-break-after: auto; }
    /* Planche A4 : badges en grille (2 × 2 ou 3 × 3), traits de découpe en pointillés */
    .sheet { position: relative; width: 210mm; height: 296mm; overflow: hidden; page-break-after: always; }
    .sheet.last { page-break-after: auto; }
    .sheet .page { position: absolute; page-break-after: auto; border: 0.2mm dashed #94a3b8; }
    .head { background: #0B1426; color: #fff; height: {{ $mm(22) }}; border-bottom: {{ $mm(1.5) }} solid #FF7900; }
    .head table { width: 100%; height: {{ $mm(20) }}; border-collapse: collapse; }
    .head td { padding: 0 {{ $mm(5) }}; vertical-align: middle; }
    .logo { width: {{ $mm(10) }}; height: {{ $mm(10) }}; background: #FF7900; color: #fff; font-size: {{ $pt(7) }}; font-weight: bold; padding: {{ $mm(5.5) }} 0 0 {{ $mm(1) }}; }
    .brand { font-size: {{ $pt(9) }}; font-weight: bold; line-height: 1.2; padding-left: {{ $mm(2.5) }}; }
    .aws { text-align: right; line-height: 1; }
    .aws b { font-size: {{ $pt(15) }}; display: block; } .aws span { font-size: {{ $pt(7.5) }}; }
    .title { text-align: center; margin-top: {{ $mm(4) }}; font-size: {{ $pt(7.5) }}; font-weight: bold; letter-spacing: {{ $pt(1.5) }}; color: #475569; }
    .photo { width: {{ $mm(26) }}; height: {{ $mm(26) }}; margin: {{ $mm(3) }} auto 0; border: {{ $mm(0.8) }} solid #FF7900; background: #0B1426; color: #fff; text-align: center; overflow: hidden; }
    .photo img { width: {{ $mm(26) }}; height: {{ $mm(26) }}; }
    .photo span { display: block; font-size: {{ $pt(22) }}; font-weight: bold; padding-top: {{ $mm(6) }}; }
    .name { text-align: center; margin-top: {{ $mm(2.5) }}; font-size: {{ $pt(12) }}; line-height: 1.2; }
    .function { text-align: center; font-size: {{ $pt(8.5) }}; color: #475569; margin-top: {{ $mm(0.5) }}; }
    .qr { text-align: center; margin-top: {{ $mm(2) }}; }
    .qr img { width: {{ $mm(40) }}; height: {{ $mm(40) }}; }
    .number { text-align: center; font-size: {{ $pt(11) }}; font-weight: bold; letter-spacing: {{ $pt(0.5) }}; }
    .foot { position: absolute; left: 0; right: 0; bottom: 0; height: {{ $mm(13) }}; background: #FF7900; color: #fff; text-align: center; padding-top: {{ $mm(2.5) }}; font-size: {{ $pt(9) }}; font-weight: bold; }
    .foot small { display: block; font-size: {{ $pt(6.5) }}; font-weight: normal; margin-top: {{ $mm(0.5) }}; }
</style>
</head>
<body>
@foreach (array_chunk($cards, $sheet ? $sheet[0] * $sheet[1] : 1) as $group)
    @if ($sheet)<div class="sheet {{ $loop->last ? 'last' : '' }}">@endif
    @foreach ($group as $i => $card)
    <div class="page {{ ! $sheet && $loop->parent->last ? 'last' : '' }}" @if ($sheet) style="left: {{ ($i % $sheet[0]) * $width }}mm; top: {{ intdiv($i, $sheet[0]) * $height }}mm" @endif>
        <div class="head">
            <table><tr>
                <td style="width: 60%">
                    <table style="height: auto"><tr>
                        <td style="padding:0; width: {{ $mm(10) }}"><div class="logo">ODC</div></td>
                        <td style="padding:0"><div class="brand">Orange<br>Digital Center</div></td>
                    </tr></table>
                </td>
                <td class="aws"><b>aws</b><span>re/Start</span></td>
            </tr></table>
        </div>
        <div class="title">BADGE PARTICIPANT</div>
        <div class="photo">
            @if ($card['photo'])<img src="{{ $card['photo'] }}" alt="">@else<span>{{ $card['initials'] }}</span>@endif
        </div>
        <div class="name">{{ $card['first_name'] }}<br><b>{{ mb_strtoupper($card['last_name']) }}</b></div>
        <div class="function">{{ $card['function'] ?: 'Participant' }} · {{ $card['organization'] }}</div>
        <div class="qr"><img src="{{ $card['qr'] }}" alt=""></div>
        <div class="number">{{ $card['number'] }}</div>
        <div class="foot">
            {{ $card['cohort'] }}
            @if ($card['validity'])<small>Valide : {{ $card['validity'] }}</small>@endif
        </div>
    </div>
    @endforeach
    @if ($sheet)</div>@endif
@endforeach
</body>
</html>
