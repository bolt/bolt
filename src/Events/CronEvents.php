<?php
namespace Bolt\Events;

/**
 * Definitions for all possible Cron Events.
 *
 * @codeCoverageIgnore
 */
final class CronEvents
{
    private function __construct()
    {
    }

    const CRON_HOURLY   = 'cron.Hourly';
    const CRON_DAILY    = 'cron.Daily';
    const CRON_WEEKLY   = 'cron.Weekly';
    const CRON_MONTHLY  = 'cron.Monthly';
    const CRON_YEARLY   = 'cron.Yearly';
}
