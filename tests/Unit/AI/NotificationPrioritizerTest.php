<?php

declare(strict_types=1);

use App\Domain\AI\Contracts\AgentResult;
use App\Infrastructure\AI\Notifications\NotificationPrioritizer;
use App\Infrastructure\AI\Notifications\NotificationPriority;

it('classifies high risk score as critical', function () {
    $prioritizer = new NotificationPrioritizer;
    $result = new AgentResult(data: ['score' => 0.9], summary: 'test', confidence: 0.9, metadata: []);
    expect($prioritizer->classify($result, 'daily_risk_scan'))->toBe(NotificationPriority::Critical);
});

it('classifies weekly report as normal', function () {
    $prioritizer = new NotificationPrioritizer;
    $result = new AgentResult(data: [], summary: 'test', confidence: 0.9, metadata: []);
    expect($prioritizer->classify($result, 'weekly_report'))->toBe(NotificationPriority::Normal);
});

it('classifies market briefing as low', function () {
    $prioritizer = new NotificationPrioritizer;
    $result = new AgentResult(data: [], summary: 'test', confidence: 0.9, metadata: []);
    expect($prioritizer->classify($result, 'market_briefing'))->toBe(NotificationPriority::Low);
});

it('classifies large loss as critical', function () {
    $prioritizer = new NotificationPrioritizer;
    $result = new AgentResult(data: ['totalPL' => -15000], summary: 'test', confidence: 0.8, metadata: []);
    expect($prioritizer->classify($result, 'weekly_report'))->toBe(NotificationPriority::Critical);
});
