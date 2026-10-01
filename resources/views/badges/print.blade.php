<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Impression des badges · {{ config('odc.app_name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;800&display=swap" rel="stylesheet">
    @include('badges._card-styles')
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        body { margin: 0; background: #e2e8f0; font-family: Inter, Arial, sans-serif; }
        .toolbar { position: sticky; top: 0; background: #0B1426; color: #fff; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; z-index: 1; }
        .toolbar button { background: #FF7900; color: #fff; border: 0; border-radius: 10px; padding: 10px 18px; font-weight: 700; cursor: pointer; font-size: 14px; }
        /* 9 badges par feuille (3 × 3) : la carte 90 × 128 mm est réduite à 64 × 91 mm */
        .sheet { width: 200mm; min-height: 287mm; margin: 10mm auto; background: #fff; display: grid; grid-template-columns: repeat(3, 64mm); grid-auto-rows: 91mm; gap: 3mm; justify-content: center; align-content: start; box-sizing: border-box; page-break-after: always; break-after: page; }
        .sheet:last-child { page-break-after: auto; break-after: auto; }
        .badge-card { border: .3mm dashed #94a3b8; zoom: .711; }
        @media print {
            body { background: #fff; } .toolbar { display: none; }
            .sheet { margin: 0; min-height: auto; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <span>{{ count($cards) }} badge(s) · 9 par feuille A4 · Lignes pointillées = traits de découpe</span>
    <button onclick="window.print()">Imprimer</button>
</div>
@forelse (array_chunk($cards, 9) as $page)
    <div class="sheet">
        @foreach ($page as $card) @include('badges._card', ['card' => $card]) @endforeach
    </div>
@empty
    <p style="text-align:center;padding:40px">Aucun badge actif à imprimer.</p>
@endforelse
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
</body>
</html>
