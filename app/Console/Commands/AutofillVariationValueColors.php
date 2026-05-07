<?php

namespace App\Console\Commands;

use App\Models\VariationValue;
use Illuminate\Console\Command;

class AutofillVariationValueColors extends Command
{
    protected $signature = 'variation-values:autofill-colors {--force : Overwrite existing color_hex values}';
    protected $description = 'Try to auto-fill color_hex on VariationValue rows based on text value (FR/EN dictionary).';

    private array $map = [
        // English
        'red' => '#DC2626',
        'blue' => '#2563EB',
        'bluebic' => '#2563EB',
        'green' => '#16A34A',
        'yellow' => '#EAB308',
        'orange' => '#F97316',
        'purple' => '#9333EA',
        'pink' => '#EC4899',
        'black' => '#000000',
        'white' => '#FFFFFF',
        'gray' => '#6B7280',
        'grey' => '#6B7280',
        'brown' => '#8B4513',
        'beige' => '#D4B896',
        'beig' => '#D4B896',
        'navy' => '#1E3A8A',
        'turquoise' => '#06B6D4',
        'teal' => '#0D9488',
        'gold' => '#D4AF37',
        'silver' => '#C0C0C0',
        'cream' => '#FFFDD0',
        'ivory' => '#FFFFF0',
        'lavender' => '#B57EDC',
        'lime' => '#84CC16',
        'mint' => '#98FF98',
        'maroon' => '#800000',
        'saffron' => '#F4C430',
        'saffforn' => '#F4C430',
        'olive' => '#808000',
        'fuchsia' => '#FF00FF',
        'amber' => '#FFBF00',
        'chocolate' => '#7B3F00',
        // FR
        'rouge' => '#DC2626',
        'bleu' => '#2563EB',
        'vert' => '#16A34A',
        'jaune' => '#EAB308',
        'violet' => '#9333EA',
        'rose' => '#EC4899',
        'noir' => '#000000',
        'blanc' => '#FFFFFF',
        'gris' => '#6B7280',
        'marron' => '#8B4513',
        'brun' => '#8B4513',
        'marine' => '#1E3A8A',
        'doré' => '#D4AF37',
        'dore' => '#D4AF37',
        'argent' => '#C0C0C0',
        'argenté' => '#C0C0C0',
        'argente' => '#C0C0C0',
        'crème' => '#FFFDD0',
        'creme' => '#FFFDD0',
        'écru' => '#FAF0E6',
        'ecru' => '#FAF0E6',
        'ivoire' => '#FFFFF0',
        'lavande' => '#B57EDC',
        'citron' => '#EAB308',
        'chocolat' => '#7B3F00',
        'caramel' => '#C68E17',
    ];

    private array $compound = [
        'navy blue' => '#1E3A8A',
        'blue navy' => '#1E3A8A',
        'dark blue' => '#1E40AF',
        'light blue' => '#93C5FD',
        'dark green' => '#14532D',
        'light green' => '#86EFAC',
        'dark pink' => '#BE185D',
        'light pink' => '#FBCFE8',
        'baby pink' => '#FBCFE8',
        'babypink' => '#FBCFE8',
        'dark red' => '#991B1B',
        'bleu marine' => '#1E3A8A',
        'bleu foncé' => '#1E40AF',
        'bleu fonce' => '#1E40AF',
        'bleu clair' => '#93C5FD',
        'vert foncé' => '#14532D',
        'vert fonce' => '#14532D',
        'rose pâle' => '#FBCFE8',
        'rose pale' => '#FBCFE8',
    ];

    public function handle(): int
    {
        $query = VariationValue::query()->whereHas('type', function ($q) {
            $q->whereRaw('LOWER(name) IN (?, ?)', ['color', 'couleur']);
        });
        if (! $this->option('force')) {
            $query->whereNull('color_hex');
        }
        $values = $query->get();
        $updated = 0;
        $skipped = 0;
        $lines = [];

        foreach ($values as $v) {
            $hex = $this->guess($v->value);
            if ($hex) {
                $v->color_hex = $hex;
                $v->save();
                $updated++;
                $lines[] = sprintf('  ✓ %-40s → %s', $v->value, $hex);
            } else {
                $skipped++;
            }
        }

        foreach ($lines as $l) {
            $this->line($l);
        }
        $this->info("Updated: {$updated} | No match: {$skipped}");

        return self::SUCCESS;
    }

    private function guess(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }
        $norm = mb_strtolower(trim($raw));
        $norm = str_replace(['_'], ' ', $norm);

        if (isset($this->compound[$norm])) {
            return $this->compound[$norm];
        }
        foreach ($this->compound as $phrase => $hex) {
            if (str_contains($norm, $phrase)) {
                return $hex;
            }
        }

        $tokens = preg_split('/[-\s&\/,()]+/u', $norm, -1, PREG_SPLIT_NO_EMPTY);
        $last = null;
        foreach ($tokens as $t) {
            $t = trim($t);
            if (isset($this->map[$t])) {
                $last = $this->map[$t];
            }
        }
        return $last;
    }
}
