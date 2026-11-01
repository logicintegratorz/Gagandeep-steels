<?php

namespace App\Support;

final class LeadReminders
{
    public static function deliver(?int $ownerId = null): int
    {
        return OverdueAlerts::deliver();
    }
}

