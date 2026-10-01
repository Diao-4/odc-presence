<?php

return [
    'app_name'      => 'ODC Présence',
    'organization'  => 'Orange Digital Center',
    'program'       => 'AWS re/Start',
    'cohort'        => 'P11 — Après-midi',
    'tagline'       => 'Application de gestion des présences par badge',

    // Numéro lisible imprimé sur le badge (ODC-00452) et préfixe du jeton contenu dans le QR Code
    'badge_prefix'  => 'ODC-',
    'token_prefix'  => 'ODC-BDG-',

    // Noms des fichiers exportés
    'files' => [
        'attendances' => 'ODC_AWS_ReStart_P11_Presences',
        'badges'      => 'ODC_AWS_ReStart_P11_ApresMidi_Badges',
    ],
];
