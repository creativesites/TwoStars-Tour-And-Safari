<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

/**
 * Deterministic answers for the questions visitors ask most. Used when the AI model is
 * unavailable, so the assistant never replies to a price question with a generic handoff.
 * Facts here are the confirmed ones from the client; keep them in step with the tour data.
 */
final class FactAnswers
{
	public static function reply(string $message): ?string
	{
		$text = strtolower($message);

		if ($text === '') {
			return null;
		}

		$whatsapp = self::whatsapp();
		$tours    = home_url('/tours/');

		$intents = [
			[
				'/\b(cruise|ship|port day|shore|disembark|back to the (ship|boat))\b/',
				'Yes, we collect guests from the Walvis Bay cruise port and plan the day around your ship\'s arrival and departure times. Groups are limited to 4 guests per vehicle, and the Sandwich Harbour tour is from US$135 per adult. Send your ship\'s times on WhatsApp (' . $whatsapp . ') and the team will confirm the best tour for your port day: ' . $tours,
			],
			[
				'/\b(price|prices|cost|costs|how much|rate|rates|fee|fees|children|child|kids|kid|infant|half price)\b/',
				'The Sandwich Harbour tour is US$135 per adult. Children under 6 go free, ages 6 to 11 pay half price, and guests aged 12 and over pay the full adult price. Prices for our other tours are confirmed on request, and private tours are quoted by route and group size. You can cancel up to 24 hours before the experience starts for a full refund. See the tours here: ' . $tours,
			],
			[
				'/\b(group size|how many (people|guests|persons)|small group|per vehicle|capacity|seats|fortuner)\b/',
				'We keep groups small: a maximum of 4 guests per vehicle, plus your guide and driver, in a Toyota Fortuner. That leaves time for long photo stops and questions.',
			],
			[
				'/\b(pick ?-?up|pickup|collect|hotel|meeting point|meet you|where do (you|we) (start|meet))\b/',
				'We collect from Walvis Bay hotels, Swakopmund hotels and the Walvis Bay cruise port, and drop you back at the same place. Tell us where you are staying when you book and the team will confirm your pickup time.',
			],
			[
				'/\b(cancel|cancellation|refund|change my booking|reschedule)\b/',
				'Yes. You can cancel up to 24 hours before the experience starts for a full refund. For anything closer than that, message the team on WhatsApp (' . $whatsapp . ') and they will help.',
			],
			[
				'/\bsunset\b/',
				'We can offer a sunset departure on request, depending on the season and availability. Departures are usually mid-afternoon, and the timing changes through the year, so message the team on WhatsApp (' . $whatsapp . ') with your date and they will confirm before you book.',
			],
			[
				'/\b(what time|departure|depart|leave|start time|sunset|morning|afternoon|when does)\b/',
				'The morning tour departs at 08:45 and the afternoon tour at 12:45. Sunset departures are on request in season, subject to availability. Spitzkoppe is a full-day tour and your pickup time is confirmed when you book.',
			],
			[
				'/\b(language|english|german|deutsch|chinese|french|speak)\b/',
				'Our tours are guided in English.',
			],
			[
				'/\b(private|own vehicle|custom|tailor|just us|only us|design my own)\b/',
				'Private tours give you your own vehicle, route and pace. Tell us your date, group size, interests and pickup point and the team will confirm a plan and a price. You can message them on WhatsApp (' . $whatsapp . ') or start here: ' . self::tour_link('private-namib-coast-tour', home_url('/booking/')),
			],
			[
				'/\b(who (are|runs)|tuli|women|owner|about (you|us|two stars)|your team|guide[s]? (are|is)|registered|ntb)\b/',
				'Two Stars Tour & Safari is a local family business run by Tuli and her women-led team in Walvis Bay, registered with the Namibia Tourism Board (#TSO01736). We keep groups to 4 guests per vehicle so nobody is rushed.',
			],
			[
				'/\b(sandwich harbou?r|dunes?|4x4|photo|photograph)/',
				'For dunes where the desert meets the Atlantic, the Sandwich Harbour 4x4 tour is our strongest fit: small group, long unhurried photo stops, from US$135 per adult. ' . self::tour_link('sandwich-harbour-tour', $tours),
			],
			[
				'/\b(flamingo|flamingos|pink|salt lake|bird)/',
				'The Flamingo and Pink Salt Lake tour is a shorter, relaxed outing built around birdlife and photography. Ask the team for the current price. ' . self::tour_link('walvis-bay-flamingo-tour', $tours),
			],
			[
				'/\b(spitzkoppe|granite|rock)/',
				'The Spitzkoppe tour is a full day among the granite formations, with a small group of up to 4. Ask the team for the current price. ' . self::tour_link('spitzkoppe-day-tour', $tours),
			],
			[
				'/\b(swakopmund|dune 7|town)/',
				'The Swakopmund and Dune 7 tour covers the town\'s landmarks and history, then a desert stop at Dune 7 for photos. Ask the team for the current price. ' . self::tour_link('swakopmund-city-tour-dune-7', $tours),
			],
		];

		foreach ($intents as [$pattern, $answer]) {
			if (preg_match($pattern, $text) === 1) {
				return $answer;
			}
		}

		return null;
	}

	private static function whatsapp(): string
	{
		$settings = get_option('twostars_platform_settings', []);
		$number   = is_array($settings) ? trim((string) ($settings['whatsapp_number'] ?? '')) : '';

		return $number !== '' ? $number : '+264 81 206 0111';
	}

	private static function tour_link(string $slug, string $fallback): string
	{
		$tour = get_page_by_path($slug, OBJECT, 'tour');

		return $tour instanceof \WP_Post ? (string) get_permalink($tour) : $fallback;
	}
}
