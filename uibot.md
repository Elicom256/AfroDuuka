# Task: Build an AfroDuuka Public Landing-Page Chatbot Powered by TinyFish

You are working on the existing **AfroDuuka** project. Implement a polished, responsive public-facing chatbot on the existing landing page that helps visitors understand AfroDuuka, its features, pricing, and how to get started.

**Frontend-first task:** Keep changes focused on the existing React + TypeScript frontend. Do not refactor the application or modify existing inventory, authentication, subscription, or business-management logic.

## 1. Inspect the Existing Project First

- Identify the landing page, frontend framework, existing UI components, styling conventions, and API integration patterns.
- Inspect the existing Gemini API integration and preserve it. Do not replace Gemini or break its existing functions and function-calling capabilities.
- Check whether a suitable Laravel API endpoint or secure server-side integration already exists before adding anything new.
- Reuse existing components and dependencies wherever practical.

## 2. Build the Chatbot UI

Add a floating chatbot launcher to the public AfroDuuka landing page.

The chatbot should include:

- A floating button in the bottom-right corner.
- A responsive chat panel that works on desktop and mobile.
- A welcoming message introducing AfroDuuka.
- Suggested questions, such as:
  - What is AfroDuuka?
  - What features does it offer?
  - Which subscription plans are available?
  - How can I get started?
  - Does AfroDuuka support WhatsApp notifications?
- A message input, send button, Enter-to-send support, and clear conversation history option.
- Distinct user and assistant messages, timestamps if appropriate, loading indicators, and readable error states.
- A way to minimize and reopen the chat.
- Links to relevant public pages where appropriate.
- Styling consistent with AfroDuuka's existing branding and design system.

Ensure the chatbot does not obstruct existing landing-page content, navigation, or mobile interactions.

## 3. Add AfroDuuka Product Knowledge

Create or reuse a small, maintainable knowledge source containing verified public information about AfroDuuka.

Use existing project documentation and publicly available product information to cover:

- Product overview and intended users.
- Inventory and stock management.
- Sales and purchases.
- Branches and business operations.
- Reports and profit-and-loss features.
- Subscription plans and pricing.
- WhatsApp notifications.
- Registration and onboarding.
- Frequently asked questions and support guidance.

Use only information that can be verified. Do not invent features, prices, integrations, or policies. If something is unknown, the chatbot should acknowledge that and direct the visitor to the appropriate contact or registration page.

Keep this knowledge separate from the chatbot component so it can be updated easily.

## 4. Integrate TinyFish for Real Live-Web Retrieval

This is essential because the project is intended to demonstrate a working TinyFish-powered application.

- Consult the current official TinyFish documentation to determine the correct Search or Fetch API endpoint, authentication method, request schema, and response format.
- Use TinyFish Fetch to retrieve relevant content from AfroDuuka's live public website or public documentation when answering suitable questions.
- Use TinyFish Search only where it provides additional value, such as finding relevant public information.
- Ground answers in the curated product knowledge and retrieved live content.
- Display relevant source links when available.
- Handle empty responses, timeouts, rate limits, and API failures gracefully.
- Avoid calling TinyFish unnecessarily for every message when the answer can reliably be provided from the local product knowledge.
- Do not simulate TinyFish responses or create a fake integration. The implementation must make a genuine API call and demonstrate its result.

## 5. Preserve Secure Architecture

- Never expose TinyFish API keys, Gemini API keys, or other secrets in frontend code, bundled assets, or browser-visible environment variables.
- If a secure Laravel endpoint already exists and is suitable, reuse it. Otherwise, implement only the minimal server-side proxy or endpoint needed to call TinyFish securely.
- Store any new secret in the appropriate server-side environment configuration and document the required variable names.
- Keep the chatbot public and read-only. It must not access private customer records, tenant-specific data, or authenticated business dashboards.
- Validate inputs, limit excessive requests, and handle errors safely.
- Do not change existing Gemini business logic or introduce a second general-purpose LLM integration unless the current architecture genuinely requires it. Prefer the existing Gemini integration for answer generation if it can be reused safely.

## 6. Testing and Acceptance Criteria

The task is complete when:

1. The chatbot appears on the existing public landing page.
2. Its interface works on desktop and mobile.
3. Visitors can ask questions about AfroDuuka and receive grounded responses.
4. A suitable query triggers a genuine TinyFish live-web retrieval request.
5. Answers use verified product information and live retrieved content where appropriate.
6. API errors do not crash the landing page.
7. No secrets are exposed in the browser.
8. Existing landing-page functionality, Gemini features, and business workflows remain intact.
9. No unrelated redesign, backend refactor, or database migration is introduced.
10. The app builds successfully and existing relevant tests pass.

## 7. Final Report

After implementation, report:

- Files created and modified.
- How the chatbot works.
- The exact TinyFish API operation used and where it is called.
- Any environment variables that must be configured.
- Any deployment steps required for Render.
- Tests and build checks performed.
- Any remaining limitations or steps I must complete manually.

**Priority:** Deliver a small, working MVP rather than an overengineered chatbot. Keep the implementation focused, secure, maintainable, and ready for demonstration as an AfroDuuka + TinyFish project.
