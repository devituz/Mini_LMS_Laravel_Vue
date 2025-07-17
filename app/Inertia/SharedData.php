<?php



namespace App\Inertia;

class SharedData
{
    public static function data(): array
    {
        return [
            'appName' => config('app.name'),
        ];
    }
}
