<?php

namespace App\Support;

use Carbon\Carbon;

class SystemPrompt
{
    public static function build(): string
    {
        $date = Carbon::now(config('app.timezone'))
            ->locale('pt_BR')
            ->isoFormat('dddd, D [de] MMMM [de] YYYY');

        return config('chatbot.system_prompt')."\n\nHoje é {$date}.";
    }
}
