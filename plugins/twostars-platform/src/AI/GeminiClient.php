<?php
declare(strict_types=1);

namespace TwoStars\Platform\AI;

use WP_Error;

final class GeminiClient
{
	/**
	 * @param array<string, mixed> $chat_context
	 * @param array<string, mixed> $grounding
	 * @return string|WP_Error
	 */
	public static function generate_chat_reply(string $message, array $chat_context = [], array $grounding = [])
	{
		return self::generate_contents(
			GeminiConfig::get_flash_model(),
			self::build_chat_contents($message, $chat_context, $grounding),
			self::build_chat_system_instruction()
		);
	}

	/**
	 * @return string|WP_Error
	 */
	public static function generate_reasoned_text(string $message)
	{
		return self::generate_text(
			GeminiConfig::get_reasoning_model(),
			$message,
			'You are handling a higher-reasoning tour planning task for Two Stars Tour and Safari in Walvis Bay and Swakopmund. ' .
			'Think carefully and return only the final answer in plain text.'
		);
	}

	/**
	 * @return array<int, float>|WP_Error
	 */
	public static function generate_embedding(string $text)
	{
		$api_key = GeminiConfig::get_api_key();

		if ($api_key === '') {
			return new WP_Error(
				'twostars_gemini_missing_key',
				__('Gemini API key is not configured.', 'twostars-platform')
			);
		}

		$model = GeminiConfig::get_embedding_model();
		$response = wp_remote_post(
			'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':embedContent',
			[
				'headers' => [
					'Content-Type' => 'application/json',
					'x-goog-api-key' => $api_key,
				],
				'timeout' => 25,
				'body'    => wp_json_encode(
					[
						'model' => 'models/' . $model,
						'content' => [
							'parts' => [
								['text' => $text],
							],
						],
					]
				),
			]
		);

		if (is_wp_error($response)) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code($response);
		$body = json_decode((string) wp_remote_retrieve_body($response), true);

		if ($status_code >= 400) {
			return new WP_Error(
				'twostars_gemini_embedding_http_error',
				(string) ($body['error']['message'] ?? __('Gemini embedding request failed.', 'twostars-platform')),
				['status' => $status_code]
			);
		}

		$values = $body['embedding']['values'] ?? [];

		if (! is_array($values) || $values === []) {
			return new WP_Error(
				'twostars_gemini_embedding_empty',
				__('Gemini embedding response was empty.', 'twostars-platform')
			);
		}

		return array_map('floatval', $values);
	}

	/**
	 * @return string|WP_Error
	 */
	private static function generate_text(string $model, string $message, string $system_instruction)
	{
		return self::generate_contents(
			$model,
			[
				[
					'role'  => 'user',
					'parts' => [
						['text' => $message],
					],
				],
			],
			$system_instruction
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $contents
	 * @return string|WP_Error
	 */
	private static function generate_contents(string $model, array $contents, string $system_instruction)
	{
		$api_key = GeminiConfig::get_api_key();

		if ($api_key === '') {
			return new WP_Error(
				'twostars_gemini_missing_key',
				__('Gemini API key is not configured.', 'twostars-platform')
			);
		}

		$response = wp_remote_post(
			'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent',
			[
				'headers' => [
					'Content-Type' => 'application/json',
					'x-goog-api-key' => $api_key,
				],
				'timeout' => 25,
					'body'    => wp_json_encode(
						[
							'systemInstruction' => [
								'parts' => [
									['text' => $system_instruction],
								],
							],
							'contents' => $contents,
							'generationConfig' => [
								'temperature'     => 0.5,
								'maxOutputTokens' => 700,
							],
						]
					),
			]
		);

		if (is_wp_error($response)) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code($response);
		$body        = json_decode((string) wp_remote_retrieve_body($response), true);

		if ($status_code >= 400) {
			$message_text = (string) ($body['error']['message'] ?? __('Gemini request failed.', 'twostars-platform'));

			return new WP_Error(
				'twostars_gemini_http_error',
				$message_text,
				['status' => $status_code]
			);
		}

		$parts = $body['candidates'][0]['content']['parts'] ?? [];
		$text  = '';

		if (is_array($parts)) {
			foreach ($parts as $part) {
				$text .= isset($part['text']) ? (string) $part['text'] : '';
			}
		}

		$text = trim($text);

		if ($text === '') {
			return new WP_Error(
				'twostars_gemini_empty_response',
				__('Gemini returned an empty response.', 'twostars-platform')
			);
		}

		return $text;
	}

	private static function build_chat_system_instruction(): string
	{
		return implode(
			"\n",
				[
					'You are the Two Stars Tour and Safari website guide for Walvis Bay, Swakopmund, Sandwich Harbour, Walvis Bay Lagoon, Pink Salt Lake, Dune 7, Spitzkoppe, and nearby coastal day tours.',
					'Speak like a practical local guide and trip planner, not like a generic travel chatbot.',
					'Be concise, practical, warm, and helpful without sounding salesy or pushy.',
					'Do not refer to yourself as an AI assistant unless the visitor explicitly asks about it.',
					'Ground your answer in the supplied site context and grounding snippets. Do not invent tours, prices, durations, inclusions, pickup zones, or destinations.',
					'Use recent conversation history when available so the visitor does not need to repeat themselves.',
					'The current live offer centers on Sandwich Harbour 4x4 Adventure, Flamingo and Pink Lake Tour, Swakopmund City Tour + Dune 7, Spitzkoppe Day Tour, and Private Tour.',
					'The main operating base and pickup areas are Walvis Bay and Swakopmund when the supplied site context supports that.',
					'Do not describe the business as a generic Namibia safari operator and do not mention Etosha, Sossusvlei, self-drive circuits, lodges, or multi-day safari circuits unless the supplied site context explicitly contains them.',
					'Do not promise fixed prices or availability unless the supplied site context explicitly includes them. If pricing, dates, or availability are unknown, say the team can confirm them.',
					'If the visitor asks for a recommendation, lead with the strongest-fit tour first, then explain why in one or two practical sentences.',
					'When comparing tours, focus on route feel, pace, highlights, pickup practicality, and who each tour suits best.',
					'When visitor context is supplied, use it quietly in the background to improve continuity and route fit.',
					'Prefer offering to continue or resume planning rather than stating what the visitor previously viewed or that they are returning, unless they explicitly ask.',
					'You may reference country or city when it materially helps with pickups, timing, or travel-planning context.',
					'Prioritize natural conversation over calls to action.',
					'Do not keep pushing booking, forms, or contact capture in every answer.',
					'Only raise the next booking step when it is actually relevant to the visitor\'s question or intent.',
					'If the visitor wants help booking, a quote, or a team follow-up, ask for one missing detail at a time in plain chat.',
					'When collecting details, ask only one question per turn, wait for the answer, and then ask the next most useful missing detail.',
					'Prefer questions like "Can I have your email?" or "How many people are traveling?" instead of showing a form.',
					'Do not output a lead form unless the visitor explicitly asks to submit their details right now.',
					'Respond with plain text first.',
					'Use UI blocks sparingly. Most answers should be plain text only.',
				'UI block format:',
				'---ui-component---',
				'{"component":"tour-highlight-card","props":{...},"metadata":{"intent_stage":"considering","primary_cta":"book_now"}}',
				'---end-ui---',
				'Supported components only:',
				'- tour-highlight-card: use for recommending one tour or destination with title, duration, summary, availability, image_url, cta_link, cta_label',
				'- comparison-table: use for comparing 2-3 tours with title, headers, rows',
				'- itinerary-timeline: use when describing a day-by-day or step-by-step route with title and items [{day,label,desc}]',
				'- lead-capture-mini: avoid this by default. Use it only if the visitor explicitly asks to send their details in a form right now.',
					'Never output unsupported components.',
					'If you use a UI block, ensure the JSON is valid and self-contained.',
					'Never invent cta_link values. Use a real grounded url or booking_url from the supplied context. If you do not have a real link, omit cta_link.',
				]
			);
	}

	/**
	 * @param array<string, mixed> $chat_context
	 * @param array<string, mixed> $grounding
	 */
	private static function build_chat_input(string $message, array $chat_context, array $grounding): string
	{
		$page_context = (array) ($chat_context['page_context'] ?? []);
		$visitor_context = (array) ($chat_context['visitor'] ?? []);
		$snippets     = array_values(array_filter((array) ($grounding['snippets'] ?? [])));

		$context_lines = array_filter(
			[
				'visitor_message: ' . $message,
				'current_persona: ' . (string) ($chat_context['current_persona'] ?? 'general_browser'),
				'intent_stage: ' . (string) ($chat_context['stage'] ?? 'browsing'),
				'journey_stage: ' . (string) ($chat_context['journey_stage'] ?? ''),
				'page_type: ' . (string) ($chat_context['page_type'] ?? ''),
				'current_page_title: ' . (string) ($page_context['title'] ?? ''),
				'current_page_summary: ' . (string) ($page_context['summary'] ?? ''),
				'visitor_country: ' . (string) ($visitor_context['country_name'] ?? ''),
				'visitor_city: ' . (string) ($visitor_context['city'] ?? ''),
				'visitor_is_returning: ' . (! empty($visitor_context['is_returning']) ? 'yes' : 'no'),
				'visitor_device: ' . trim(((string) ($visitor_context['device_family'] ?? '')) . ' ' . ((string) ($visitor_context['browser'] ?? ''))),
				'visitor_booking_count: ' . (string) ((int) ($visitor_context['booking_complete_count'] ?? 0)),
				'visitor_linked_leads: ' . implode(', ', array_map('strval', (array) ($visitor_context['lead_ids'] ?? []))),
				'visited_tours: ' . implode(', ', (array) ($chat_context['visited_tour_titles'] ?? [])),
				'visited_destinations: ' . implode(', ', (array) ($chat_context['visited_destination_titles'] ?? [])),
				'conversation_summary: ' . (string) ($chat_context['conversation_summary'] ?? ''),
				'conversation_tours: ' . implode(', ', (array) ($chat_context['conversation_tours'] ?? [])),
				'conversation_preferences: ' . implode(' | ', (array) ($chat_context['conversation_preferences'] ?? [])),
				'last_recommendation_key: ' . (string) ($chat_context['last_recommendation'] ?? ''),
			],
			static fn(string $line): bool => ! str_ends_with($line, ': ')
			);

		$sections = [
			'SITE CONTEXT',
			implode("\n", $context_lines),
		];

		if ($snippets !== []) {
			$sections[] = 'GROUNDING SNIPPETS';
			$sections[] = implode("\n", array_map(static fn(string $snippet): string => '- ' . $snippet, $snippets));
		}

		$sections[] = 'TASK';
		$sections[] = 'Answer the visitor directly. Prefer grounded details. Add a UI block only if it materially improves the experience.';

		return implode("\n\n", $sections);
	}

	/**
	 * @param array<string, mixed> $chat_context
	 * @param array<string, mixed> $grounding
	 * @return array<int, array<string, mixed>>
	 */
	private static function build_chat_contents(string $message, array $chat_context, array $grounding): array
	{
		$contents = [];

		foreach ((array) ($chat_context['conversation_history'] ?? []) as $message_row) {
			if (! is_array($message_row)) {
				continue;
			}

			$role = (string) ($message_row['role'] ?? 'user');
			$content = trim((string) ($message_row['content'] ?? ''));

			if ($content === '') {
				continue;
			}

			$contents[] = [
				'role'  => $role === 'assistant' ? 'model' : 'user',
				'parts' => [
					['text' => $content],
				],
			];
		}

		$contents[] = [
			'role'  => 'user',
			'parts' => [
				['text' => self::build_chat_input($message, $chat_context, $grounding)],
			],
		];

		return $contents;
	}
}
