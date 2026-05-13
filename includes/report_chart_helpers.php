<?php

if (!function_exists('edunexo_report_months')) {
    function edunexo_report_months(int $count = 6): array
    {
        $labels = [
            '01' => 'Ene',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Abr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Ago',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dic',
        ];

        $months = [];
        $start = new DateTime('first day of this month');
        $start->modify('-' . max(0, $count - 1) . ' months');

        for ($i = 0; $i < $count; $i++) {
            $key = $start->format('Y-m');
            $months[$key] = [
                'label' => $labels[$start->format('m')] ?? $start->format('M'),
                'value' => 0,
            ];
            $start->modify('+1 month');
        }

        return $months;
    }
}

if (!function_exists('edunexo_report_fill_months')) {
    function edunexo_report_fill_months(array $months, array $rows): array
    {
        foreach ($rows as $row) {
            $key = $row['mes'] ?? '';
            if (isset($months[$key])) {
                $months[$key]['value'] = (int) ($row['total'] ?? 0);
            }
        }

        return $months;
    }
}

if (!function_exists('edunexo_report_values')) {
    function edunexo_report_values(array $months): array
    {
        return array_map(static fn ($item) => (int) $item['value'], array_values($months));
    }
}

if (!function_exists('edunexo_report_labels')) {
    function edunexo_report_labels(array $months): array
    {
        return array_map(static fn ($item) => (string) $item['label'], array_values($months));
    }
}

if (!function_exists('edunexo_report_max')) {
    function edunexo_report_max(array ...$series): int
    {
        $max = 1;

        foreach ($series as $values) {
            foreach ($values as $value) {
                $max = max($max, (int) $value);
            }
        }

        return $max;
    }
}

if (!function_exists('edunexo_report_line_points')) {
    function edunexo_report_line_points(array $values, int $max, int $width = 520, int $height = 220, int $pad = 34): string
    {
        $count = count($values);
        if ($count === 0) {
            return '';
        }

        $usableWidth = $width - ($pad * 2);
        $usableHeight = $height - ($pad * 2);
        $step = $count > 1 ? $usableWidth / ($count - 1) : 0;
        $points = [];

        foreach (array_values($values) as $index => $value) {
            $x = $pad + ($step * $index);
            $y = $height - $pad - (((int) $value / max(1, $max)) * $usableHeight);
            $points[] = round($x, 2) . ',' . round($y, 2);
        }

        return implode(' ', $points);
    }
}

if (!function_exists('edunexo_report_bar_percent')) {
    function edunexo_report_bar_percent(int $value, int $max): int
    {
        return max(4, (int) round(($value / max(1, $max)) * 100));
    }
}
