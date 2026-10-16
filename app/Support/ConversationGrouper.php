<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConversationGrouper
{
    /** @return array<string, array<mixed>> */
    public static function group(Collection $conversations): array
    {
        $today = Carbon::today(config('app.timezone'));
        $yesterday = Carbon::yesterday(config('app.timezone'));
        $weekAgo = Carbon::today(config('app.timezone'))->subDays(7);

        $groups = ['Hoje' => [], 'Ontem' => [], 'Últimos 7 dias' => [], 'Mais antigas' => []];

        foreach ($conversations as $conv) {
            $date = Carbon::parse($conv->updated_at, config('app.timezone'))->startOfDay();

            if ($date->eq($today)) {
                $groups['Hoje'][] = $conv;
            } elseif ($date->eq($yesterday)) {
                $groups['Ontem'][] = $conv;
            } elseif ($date->gt($weekAgo)) {
                $groups['Últimos 7 dias'][] = $conv;
            } else {
                $groups['Mais antigas'][] = $conv;
            }
        }

        return array_filter($groups, fn ($g) => ! empty($g));
    }
}
