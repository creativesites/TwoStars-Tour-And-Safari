<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

final class JourneyOrchestrator
{
	/**
	 * @param array<string, mixed> $session_state
	 * @param array<int, string> $recent_events
	 * @return array<string, mixed>
	 */
	public static function build_state(array $session_state, array $recent_events = []): array
	{
		$intent_vector = self::normalize_vector((array) ($session_state['intent_vector'] ?? []));
		$signal_counts = (array) ($session_state['signal_counts'] ?? []);
		$lead_created = ! empty($session_state['lead_created']);

		$stage = 'discovery';

		if ($lead_created) {
			$stage = 'conversion';
		} elseif (
			$intent_vector['purchase_intent'] >= 65
			|| $intent_vector['urgency'] >= 55
			|| (int) ($signal_counts['availability_check'] ?? 0) >= 1
			|| in_array('booking_click', $recent_events, true)
		) {
			$stage = 'decision';
		} elseif (
			$intent_vector['research_depth'] >= 35
			|| (int) ($signal_counts['comparison_view'] ?? 0) >= 1
			|| (int) ($signal_counts['comparison_add'] ?? 0) >= 1
		) {
			$stage = 'evaluation';
		}

		$progression = match ($stage) {
			'discovery' => min(100, (int) round(($intent_vector['research_depth'] + $intent_vector['engagement_quality']) / 2)),
			'evaluation' => min(100, (int) round(($intent_vector['research_depth'] + $intent_vector['confidence']) / 2)),
			'decision' => min(100, (int) round(($intent_vector['purchase_intent'] + $intent_vector['urgency']) / 2)),
			default => 100,
		};

		$next_actions = match ($stage) {
			'discovery' => ['explore tours', 'understand routes', 'get inspired'],
			'evaluation' => ['compare options', 'review inclusions', 'assess fit'],
			'decision' => ['check availability', 'confirm route', 'prepare inquiry'],
			default => ['complete handoff'],
		};

		return [
			'stage' => $stage,
			'progression' => $progression,
			'next_actions' => $next_actions,
		];
	}

	/**
	 * @param array<string, mixed> $vector
	 * @return array<string, int>
	 */
	private static function normalize_vector(array $vector): array
	{
		return [
			'purchase_intent' => (int) ($vector['purchase_intent'] ?? 0),
			'research_depth' => (int) ($vector['research_depth'] ?? 0),
			'price_sensitivity' => (int) ($vector['price_sensitivity'] ?? 0),
			'urgency' => (int) ($vector['urgency'] ?? 0),
			'confidence' => (int) ($vector['confidence'] ?? 0),
			'engagement_quality' => (int) ($vector['engagement_quality'] ?? 0),
		];
	}
}
