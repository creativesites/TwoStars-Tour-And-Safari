<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

final class InterventionTimingEngine
{
	/**
	 * @param array<string, mixed> $session_state
	 * @param array<int, string> $recent_events
	 * @return array<string, mixed>
	 */
	public static function predict(array $session_state, array $recent_events, string $journey_stage): array
	{
		$intent_vector = self::normalize_vector((array) ($session_state['intent_vector'] ?? []));
		$signal_counts = (array) ($session_state['signal_counts'] ?? []);

		$decision_point = self::calculate_decision_point_score($recent_events, $signal_counts, $intent_vector);
		$engagement_momentum = self::calculate_engagement_momentum($recent_events, $intent_vector);
		$friction = self::calculate_friction_score($recent_events);
		$readiness = (int) round(($decision_point * 0.45) + ($engagement_momentum * 0.35) + ($friction * 0.20));

		return [
			'ready' => $readiness >= 45,
			'score' => $readiness,
			'decision_point' => $decision_point,
			'engagement_momentum' => $engagement_momentum,
			'friction' => $friction,
			'intervention_type' => self::choose_intervention_type($journey_stage, $decision_point, $friction),
		];
	}

	/**
	 * @param array<int, string> $recent_events
	 * @param array<string, mixed> $signal_counts
	 * @param array<string, int> $intent_vector
	 */
	private static function calculate_decision_point_score(array $recent_events, array $signal_counts, array $intent_vector): int
	{
		$score = 0;

		foreach ($recent_events as $event_name) {
			$score += match ($event_name) {
				'pricing_interest' => 12,
				'date_selector_open' => 18,
				'availability_check' => 22,
				'comparison_view' => 14,
				'booking_click' => 24,
				'lead_form_open' => 18,
				default => 0,
			};
		}

		$score += min(14, (int) ($signal_counts['availability_check'] ?? 0) * 7);
		$score += min(14, (int) ($signal_counts['comparison_view'] ?? 0) * 7);
		$score += (int) round($intent_vector['purchase_intent'] * 0.18);

		return min(100, $score);
	}

	/**
	 * @param array<int, string> $recent_events
	 * @param array<string, int> $intent_vector
	 */
	private static function calculate_engagement_momentum(array $recent_events, array $intent_vector): int
	{
		$score = 0;

		foreach ($recent_events as $event_name) {
			$score += match ($event_name) {
				'scroll_80', 'gallery_view', 'review_read', 'faq_expand', 'chat_message' => 10,
				'comparison_add', 'comparison_view' => 12,
				default => 0,
			};
		}

		$score += (int) round($intent_vector['research_depth'] * 0.2);
		$score += (int) round($intent_vector['engagement_quality'] * 0.2);

		return min(100, $score);
	}

	/**
	 * @param array<int, string> $recent_events
	 */
	private static function calculate_friction_score(array $recent_events): int
	{
		$score = 0;

		foreach ($recent_events as $event_name) {
			$score += match ($event_name) {
				'pricing_interest' => 6,
				'comparison_view' => 4,
				default => 0,
			};
		}

		return min(100, $score);
	}

	private static function choose_intervention_type(string $journey_stage, int $decision_point, int $friction): string
	{
		if ($friction >= 40) {
			return 'assistance';
		}

		if ($decision_point >= 55 || $journey_stage === 'decision') {
			return 'booking_assist';
		}

		if ($journey_stage === 'evaluation') {
			return 'comparison';
		}

		return 'guidance';
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
