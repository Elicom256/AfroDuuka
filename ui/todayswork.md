# Today's work on duukaflow (Do NOT exceed scope)

## Goal

Build a modern, user-friendly login and signup flow using existing components.

## Context

- Components already exist in: src/app/pages/public
- Use Shadcn UI components (Card, Input, Button, etc.)
- Use Sonner for toast notifications
- Use Lucide icons where appropriate
- App supports dark mode → UI must adapt correctly

## Requirements

### Login Page

- Centered card layout
- Fields: email, password
- Show validation errors (required fields, invalid email)
- Submit button with loading state
- Link: "Don't have an account? Sign up" → navigates to signup page

### Signup Page

- Centered card layout
- Fields: name, email, phone, password
- Validate:
  - email format
  - password minimum length (e.g. 6+ chars)
  - required fields
- Show toast (Sonner) on successful signup
- Link: "Already have an account? Login"

## UX Expectations

- Clean, modern UI (minimal, good spacing)
- Proper error messages under inputs
- Disabled button while submitting
- Accessible (labels, focus states)

## Constraints

- Do NOT create custom UI components if Shadcn provides them
- Do NOT add fields beyond: name, email, phone, password
- Do NOT invent backend logic—only UI and form handling

## Output

- Clean, readable React/Next.js code
- Reusable form structure where possible
