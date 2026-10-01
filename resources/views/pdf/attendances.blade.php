<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 16mm 12mm 18mm 12mm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #0B1426; }
    .header { border-bottom: 2px solid #FF7900; padding-bottom: 8px; margin-bottom: 12px; }
    .header table { width: 100%; }
    .logo { display: inline-block; width: 30px; height: 30px; background: #FF7900; color: #fff; font-size: 7pt; font-weight: bold; padding: 17px 0 0 3px; box-sizing: border-box; vertical-align: middle; }
    .org { display: inline-block; vertical-align: middle; margin-left: 8px; font-weight: bold; font-size: 10pt; line-height: 1.2; }
    h1 { font-size: 16pt; margin: 4px 0 2px; letter-spacing: 0.5pt; }
    .muted { color: #64748b; }
    .stats { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 -6px 14px; }
    .stats td { border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 9px; width: 16.6%; }
    .stats .v { font-size: 14pt; font-weight: bold; display: block; }
    table.list { width: 100%; border-collapse: collapse; }
    table.list th { background: #0B1426; color: #fff; text-align: left; padding: 6px 6px; font-size: 8pt; }
    table.list td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; }
    table.list tr:nth-child(even) td { background: #f8fafc; }
    .present { color: #16A34A; font-weight: bold; } .late { color: #E86C00; font-weight: bold; } .absent { color: #DC2626; font-weight: bold; }
    .footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7.5pt; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 4px; }
    .pagenum:before { content: counter(page); }
</style>
</head>
<body>
<div class="footer">
    <table style="width:100%"><tr>
        <td>Document généré automatiquement par {{ config('odc.app_name') }} le {{ now()->format('d/m/Y à H:i') }}</td>
        <td style="text-align:right">Page <span class="pagenum"></span></td>
    </tr></table>
</div>

<div class="header">
    <table><tr>
        <td><span class="logo">ODC</span><span class="org">{{ config('odc.organization') }}<br><span class="muted" style="font-weight:normal">{{ config('odc.program') }} {{ config('odc.cohort') }}</span></span></td>
        <td style="text-align:right; vertical-align:bottom">
            <h1>LISTE DES PRÉSENCES</h1>
            <span class="muted">Période : {{ $from->format('d/m/Y') }}@unless($from->isSameDay($to)) → {{ $to->format('d/m/Y') }}@endunless</span>
        </td>
    </tr></table>
</div>

<table class="stats"><tr>
    <td><span class="muted">Total participants</span><span class="v">{{ $summary['participants'] }}</span></td>
    <td><span class="muted">Présents</span><span class="v present">{{ $summary['present'] }}</span></td>
    <td><span class="muted">Retards</span><span class="v late">{{ $summary['late'] }}</span></td>
    <td><span class="muted">Absents</span><span class="v absent">{{ $summary['absent'] }}</span></td>
    <td><span class="muted">Taux de présence</span><span class="v">{{ $summary['rate'] }} %</span></td>
    <td><span class="muted">Arrivée moyenne</span><span class="v">{{ $summary['avg_arrival'] }}</span></td>
</tr></table>

<table class="list">
    <thead><tr><th style="width:5%">N°</th><th>Nom et prénom</th><th>Badge</th><th>Date</th><th>Heure</th><th>Statut</th><th>Retard</th></tr></thead>
    <tbody>
    @forelse ($rows as $r)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $r['name'] }}</td>
            <td>{{ $r['badge'] }}</td>
            <td>{{ $r['date']->format('d/m/Y') }}</td>
            <td>{{ $r['arrived'] ?? '—' }}@if($r['departed']) <span class="muted">→ {{ $r['departed'] }}</span>@endif</td>
            <td class="{{ $r['status'] }}">{{ $r['status_label'] }}@if ($r['justified'])<br><span class="muted">{{ \Illuminate\Support\Str::limit($r['justification']->reason, 60) }}</span>@endif</td>
            <td>{{ $r['status'] === 'absent' ? '—' : $r['late_minutes'].' min' }}</td>
        </tr>
    @empty
        <tr><td colspan="7" style="text-align:center; padding:20px" class="muted">Aucune donnée pour cette période.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
