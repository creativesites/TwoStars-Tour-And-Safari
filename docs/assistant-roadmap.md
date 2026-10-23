# Two Stars assistant: state of play and improvement plan

Written 23 Oct 2026. The assistant is the floating "Two Stars Guide" (launcher, panel, nudge, history, handoff form),
built in `plugins/twostars-platform` (`src/CRM/Chatbot.php`, `assets/js/chatbot.js`, `assets/css/chatbot.css`,
`src/AI/*`, `src/Rest/Routes.php`).

## 1. The most important finding: the AI is switched off

Every request to the AI model fails with:

> Lightning dunning decision is deny for project: projects/201873778892

That is Google Cloud refusing the request, almost certainly a billing or payment problem on the project that owns
the Gemini API key. Until it is fixed, visitors only ever get the offline fallback replies, which is also what we
saw in testing. Action for the owner: open Google Cloud Console, check billing for that project, and re-run a test
chat. Everything below that mentions "AI answers" depends on this.

Related risks found in the same code:
- The API key sits in plain text in the `twostars_platform_settings` option. Move it to a `wp-config.php` constant
  on production and rotate it once billing is sorted.
- The configured models are preview models (`gemini-3.1-*-preview`). Pin stable model names before launch so a
  preview being retired does not silently kill the assistant.

## 2. Done in this round

Interface
- Panel now anchors above the launcher (before, opening it pushed the launcher to the top of the screen).
- Panel sizes to its content (no big empty area) and scrolls the message list only; input is no longer clipped.
- Quieter header: name, an "AI guide" line, a green dot, a **WhatsApp button to reach the human team**, new chat,
  history, close. Removed the internal jargon pill ("Exploring / Planning") and the extra context chips.
- Quick prompts rewritten for what visitors actually ask: Prices, Cruise day, Pickups and group size, Best for
  photos, Private tour, plus "About this tour" on tour pages. They wrap instead of hiding in a scroll strip.
- Links in answers become tappable buttons ("View this tour", "Start booking", tappable WhatsApp number).
- On phones the open assistant is a full-screen sheet above the sticky Book now / WhatsApp bar.
- Contrast and type sizes brought to the rest of the site's scale.

Behaviour and safety
- **Stale facts removed from the AI prompt.** It listed the old tour names and told the model not to quote prices.
  It now receives the confirmed facts (prices, children, max 4 per vehicle, departures, free cancellation,
  English, pickups, contact) and a live tour list with links, built from tour data so it stays current.
- **Offline fact answers** (`src/AI/FactAnswers.php`): prices and children, group size, pickups, cruise guests,
  cancellation, departure times, languages, private tours, who we are, and each tour. Before this, a price
  question got a generic "this sounds like a booking inquiry" message.
- **Rate limit** on the public chat endpoint: 20 messages per visitor per 10 minutes. It was an open proxy to a
  paid API.
- Message list is now a live region so screen readers announce replies.

## 3. Plan

### Phase 1: before launch (about 1 week)
1. Fix Google Cloud billing, then test 20 real questions (list in section 4) and read every answer.
2. One source of truth for facts. Today the same facts exist in three places: tour meta, the AI prompt and
   `FactAnswers.php`. Add an "Assistant facts" box to the platform settings page (prices, child rule, cap,
   times, cancellation, languages, pickups) and have the prompt, the offline answers and the site read from it.
3. Tracking: fire GA4 events `chat_open`, `chat_prompt_click`, `chat_message`, `chat_handoff`, `chat_whatsapp`
   (the click events already use the `data-twostars-event` hook).
4. Honesty and privacy: the header now says "AI guide"; add a one-line notice with a link to /privacy/ once
   that page exists. Chat stores conversation history, so this must be covered there.
5. Make sure the assistant knows about each page change: re-index grounding content whenever a tour, page or
   FAQ is saved.
6. Accessibility pass: focus moves into the panel on open and back to the launcher on close, Esc closes,
   visible focus rings, test with VoiceOver.

### Phase 2: make it sell and serve (launch to +1 month)
1. **Booking-aware.** Every recommendation ends with a tour card showing photo, price and a Book button that
   deep-links to that tour's Bokun widget (the brief asks for this). Replace plain links with one card
   component.
2. **Cruise mode.** Ask three things, one at a time: ship name, arrival time, all-aboard time. Recommend the tour
   that fits and show the timing; hand off to WhatsApp if the margin is tight. Do not promise a back-to-ship
   guarantee in chat until the client confirms that policy.
3. **Warm handoff.** The WhatsApp button pre-fills a message with the conversation summary and the tour
   discussed, so the team never asks the same questions twice. Show "Typically replies within X" only once the
   client confirms a real figure.
4. Remember the visitor across pages (already partly built) but never say what they viewed unless asked.
5. German: prepare strings and the prompt for a German mode alongside the German tour pages.
6. Lazy-load: the chat script and styles (about 56 KB) load on every page. Load them on first open or first
   idle, which helps the mobile LCP target.

### Phase 3: improve with data (month 2 on)
1. Weekly report: top questions, questions the assistant could not answer, handoff rate, chats that ended in a
   booking or an inquiry. Unanswered questions become FAQ entries and guide articles.
2. A small regression set of 50 questions with expected facts, run after every prompt or content change.
3. Tune the proactive nudge (what triggers it, how often, on which pages) from the numbers, not guesses.
4. Thumbs up/down on replies, reviewed weekly.
5. Cost guard: daily message cap and an alert when usage jumps.

### Success measures
- Share of chats answered without a human (target above 70%).
- Chat to inquiry or booking rate, compared with visitors who never open chat.
- WhatsApp clicks from inside chat.
- Replies flagged wrong per 100 chats (target near zero; any wrong price is a bug).

## 4. Test questions to run once the AI is back
How much is Sandwich Harbour and what do kids pay? Can you pick us up from the cruise port and get us back for
all-aboard at 17:00? Is there a vegetarian option? What if it's windy? Do you do sunrise? Can we bring a drone?
Which is better for photography, Sandwich Harbour or Spitzkoppe? Do you speak German? How do I pay? Can I
change the date? Is it safe? Tell me a joke. (Expected: stays on topic, never invents a price, time or policy,
and offers WhatsApp when unsure.)

## 5. Open questions for the client
- Exact free-cancellation window.
- Whether a back-to-ship guarantee is a real policy.
- Real reply-time figure for WhatsApp.
- Sunset tour: is it a regular product, and which tours does it apply to?
- Payment methods (the old FAQ said cash, EFT and card; confirm, then update the facts box).
