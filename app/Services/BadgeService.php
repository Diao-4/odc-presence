<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Cohort;
use App\Models\Participant;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class BadgeService
{
    /** Planches A4 à imprimer puis découper : [colonnes, lignes]. */
    public const SHEETS = [
        'a4x9' => [3, 3],
        'a4x4' => [2, 2],
    ];

    /** Tailles de badge proposées au téléchargement : libellé, largeur et hauteur du badge en mm (portrait). */
    public const SIZES = [
        'a4x9' => ['Planche A4 — 9 badges par feuille (70 × 99 mm)', 70, 98.5], // 3 × 98,5 mm tient dans la hauteur utile A4
        'a4x4' => ['Planche A4 — 4 badges par feuille (105 × 148 mm)', 105, 148],
        'cr80' => ['Carte CR80 (54 × 86 mm)', 54, 85.6],
        'a7'   => ['A7 (74 × 105 mm)', 74, 105],
        'a6'   => ['A6 (105 × 148 mm)', 105, 148],
        'a5'   => ['A5 (148 × 210 mm)', 148, 210],
    ];

    public function __construct(private SettingService $settings) {}

    /** Attribue l'identifiant unique lisible (ODC-00452) au participant. */
    public function assignCode(Participant $participant): void
    {
        if (! $participant->code) {
            $participant->code = config('odc.badge_prefix').str_pad((string) $participant->id, 5, '0', STR_PAD_LEFT);
            $participant->save();
        }
    }

    /**
     * Émet un nouveau badge actif. Les badges actifs précédents sont marqués « remplacés »
     * et leur QR Code est immédiatement refusé au scan.
     */
    public function issue(Participant $participant): Badge
    {
        return DB::transaction(function () use ($participant) {
            $this->assignCode($participant);

            $participant->badges()
                ->where('status', Badge::ACTIVE)
                ->update(['status' => Badge::REPLACED, 'disabled_at' => now()]);

            return $participant->badges()->create([
                'number'    => $participant->code,
                'token'     => $this->newToken(),
                'version'   => (int) $participant->badges()->max('version') + 1,
                'status'    => Badge::ACTIVE,
                'issued_at' => now(),
            ]);
        });
    }

    public function disable(Badge $badge): void
    {
        $badge->update(['status' => Badge::DISABLED, 'disabled_at' => now()]);
    }

    /** Réactive un badge désactivé, si le participant n'a pas déjà un autre badge actif. */
    public function enable(Badge $badge): bool
    {
        $hasOtherActive = Badge::where('participant_id', $badge->participant_id)
            ->where('status', Badge::ACTIVE)
            ->whereKeyNot($badge->id)
            ->exists();

        if ($hasOtherActive || $badge->status === Badge::REPLACED) {
            return false;
        }

        $badge->update(['status' => Badge::ACTIVE, 'disabled_at' => null]);

        return true;
    }

    /** Jeton opaque contenu dans le QR Code : aucune donnée personnelle. */
    public function newToken(): string
    {
        do {
            $token = config('odc.token_prefix').strtoupper(bin2hex(random_bytes(6)));
        } while (Badge::where('token', $token)->exists());

        return $token;
    }

    /**
     * Lien encodé dans le QR Code. Lu par n'importe quel scanner (appareil photo du téléphone…),
     * il ouvre le scanner de l'application, ou la page de connexion si l'on n'est pas connecté.
     * Le jeton reste opaque : aucune donnée personnelle dans le lien.
     */
    public function scanUrl(string $token): string
    {
        $base = rtrim((string) config('app.url'), '/');

        // APP_URL laissée sur localhost : un téléphone ne pourrait pas l'ouvrir, on prend l'adresse de la requête.
        if ($base === '' || preg_match('~^https?://(localhost|127\.0\.0\.1)(:\d+)?$~i', $base)) {
            return route('badge.link', $token);
        }

        return $base.'/b/'.$token;
    }

    public function qrDataUri(string $token, int $size = 320): string
    {
        $qr = new QrCode(
            data: $this->scanUrl($token),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 8,
        );

        return (new PngWriter())->write($qr)->getDataUri();
    }

    public function photoDataUri(Participant $participant): ?string
    {
        $disk = Storage::disk('public');

        if (! $participant->photo_path || ! $disk->exists($participant->photo_path)) {
            return null;
        }

        $mime = $disk->mimeType($participant->photo_path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($participant->photo_path));
    }

    /** Données d'un badge prêtes pour l'affichage HTML ou PDF. */
    public function cardData(Badge $badge, int $qrSize = 360): array
    {
        $participant = $badge->participant;

        return [
            'first_name'   => $participant->first_name,
            'last_name'    => $participant->last_name,
            'full_name'    => $participant->full_name,
            'initials'     => $participant->initials,
            'function'     => $participant->function,
            'organization' => $participant->organization ?: config('odc.organization'),
            'number'       => $badge->number,
            'token'        => $badge->token,
            'status'       => $badge->status,
            'qr'           => $this->qrDataUri($badge->token, $qrSize),
            'photo'        => $this->photoDataUri($participant),
            'validity'     => $this->settings->get('badge_validity'),
            'cohort'       => $participant->cohort?->label ?? trim(config('odc.program').' '.config('odc.cohort')),
        ];
    }

    /** Badges actifs des participants actifs (d'une cohorte, ou de toutes), triés par nom. */
    public function activeBadges(?Cohort $cohort = null): Collection
    {
        return Badge::with('participant.cohort')
            ->where('status', Badge::ACTIVE)
            ->whereHas('participant', fn ($q) => $q->where('is_active', true)->whereNull('participants.deleted_at')->inCohort($cohort))
            ->get()
            ->sortBy(fn (Badge $b) => mb_strtolower($b->participant->last_name.' '.$b->participant->first_name))
            ->values();
    }

    /** Taille demandée si elle existe, sinon la taille par défaut des paramètres. */
    public function size(?string $size): string
    {
        if (isset(self::SIZES[$size])) {
            return $size;
        }

        $default = (string) $this->settings->get('badge_size', 'a6');

        return isset(self::SIZES[$default]) ? $default : 'a6';
    }

    /** Pour un fichier contenant un seul badge, une planche n'a pas de sens : on garde le badge seul en A6. */
    public function singleSize(?string $size): string
    {
        $size = $this->size($size);

        return isset(self::SHEETS[$size]) ? 'a6' : $size;
    }

    /** PDF : un badge par page, au format choisi. Le gabarit est dessiné en A6 puis mis à l'échelle. */
    public function pdf(Collection $badges, ?string $size = null)
    {
        @set_time_limit(300);

        $size  = $this->size($size);
        $sheet = self::SHEETS[$size] ?? null;
        [, $width, $height] = self::SIZES[$size];
        $toPt = 72 / 25.4;

        $pdf = Pdf::loadView('pdf.badges', [
            'cards'  => $badges->map(fn (Badge $b) => $this->cardData($b))->all(),
            'width'  => $width,
            'height' => $height,
            'k'      => min($width / 105, $height / 148),
            'sheet'  => $sheet, // [colonnes, lignes] ou null (un badge par page)
        ]);

        return $sheet ? $pdf->setPaper('a4', 'portrait') : $pdf->setPaper([0, 0, $width * $toPt, $height * $toPt]);
    }

    public function fileName(Participant $participant): string
    {
        $name = Str::of(Str::ascii($participant->first_name.' '.$participant->last_name))
            ->replaceMatches('/[^A-Za-z0-9]+/', '_')
            ->trim('_');

        return ($name->isEmpty() ? $participant->code : $name).'.pdf';
    }

    /** Archive ZIP contenant un PDF par participant. Retourne le chemin du fichier temporaire. */
    public function zip(Collection $badges, ?string $size = null): string
    {
        @set_time_limit(600);

        $dir = storage_path('app/tmp');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir.'/badges_'.Str::random(12).'.zip';
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Impossible de créer l'archive ZIP.");
        }

        $used = [];
        foreach ($badges as $badge) {
            $name = $this->fileName($badge->participant);
            if (isset($used[$name])) {
                $name = Str::beforeLast($name, '.pdf').'_'.$badge->number.'.pdf';
            }
            $used[$name] = true;

            // Un fichier par participant : la planche A4 n'a pas de sens, on garde le badge seul en A6.
            $zip->addFromString($name, $this->pdf(collect([$badge]), $this->singleSize($size))->output());
        }

        $zip->close();

        return $path;
    }
}
