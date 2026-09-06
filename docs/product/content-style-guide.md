# CybCademy — Content Style Guide

For anyone authoring course content, quiz questions, or phishing templates — whether Solunar staff expanding the starter library or a customer's Trainer building their own content. Establishes the tone/format standard the starter content library (`SeedStarterContentLibrary`) was written to, so future content is consistent with it rather than each author inventing their own voice.

---

## 1. Voice

- **Plain, direct, non-alarmist.** The goal is a confident colleague explaining something useful, not a lecture and not a scare tactic. Compare: "Phishing attacks are a growing threat that could devastate your organisation" (avoid) vs. "Phishing remains the most common way attackers get into an organisation" (prefer).
- **Assume good faith.** Employees are not the problem to be fixed — they're being trained to recognise something genuinely deceptive. Never write content that implies carelessness or blame (this matches the platform's core non-punitive design principle, carried from BR-4 into every course, not just phishing-specific ones).
- **Second person, present tense.** "You receive an email..." not "Employees may receive an email..."

## 2. Lesson Structure

- **150–400 words per text lesson.** Long enough to explain the "why," short enough to read in under two minutes — matches the platform's design principle (Phase 6) that training must fit around a real workday, not consume it.
- **Lead with why it matters, not with rules.** "Why Phishing Still Works" (explaining attacker psychology) works better as an opening lesson than "Five Rules You Must Follow."
- **One clear idea per lesson**, not a checklist trying to cover everything about a topic. A course is a sequence of focused lessons, not one giant document split arbitrarily.
- **End on something actionable**, ideally something the employee can do in the next five minutes (e.g., "hover over a link before clicking" rather than an abstract principle).

## 3. Assessment Questions

- **Test judgment, not memorisation of a list.** "You receive an email from IT asking for your password — what should you do?" is a better question than "Name three signs of a phishing email," because it mirrors the actual decision an employee will face.
- **Every incorrect option should be a plausible, realistic mistake**, not an obviously wrong throwaway answer — a multiple-choice question with one absurd distractor teaches nothing.
- **Passing score: 70% is the platform default** (see the starter library's assessments) — lower thresholds risk certifying people who didn't actually absorb the material; higher thresholds on a short quiz can feel punitive for what's meant to be a learning tool, not a high-stakes exam.
- **Correct answers must be unambiguous.** If a question could reasonably have two defensible answers, rewrite it — this matters more than usual here, since grading is fully automated and server-side (no human grader to use judgment on an edge case).

## 4. Phishing Simulation Templates

- **Realistic, but never referencing a real company, brand, or person** — this is enforced by the AI Phishing Email Generator's system prompt (Epic E9) and should be followed by human-authored templates too, both for legal reasons (trademark/impersonation) and training quality (a fake "Microsoft" email teaches different pattern-recognition than a genuinely novel lure would).
- **Use recognisable social-engineering techniques deliberately, not randomly** — urgency, authority, curiosity, fear of missing out. A good template should be identifiable, after the fact, as "this used urgency + authority" — that's what makes the post-click educational content meaningful (it can name the specific technique used).
- **Vary difficulty across a campaign library.** Not every simulation should be maximally deceptive — some obvious ones build early confidence, harder ones build real skill over time.

## 5. Policy Summary Language (for AI Policy Summariser Output Review)

When reviewing an AI-generated policy summary (Epic E9's Policy Summariser) before it's shown to employees:
- Confirm it doesn't invent an obligation not present in the source document — the AI system prompt instructs against this, but human review remains the actual control.
- Confirm it stays under roughly 150 words, matching the system prompt's constraint — a summary longer than that has stopped being a summary.

## 6. What to Avoid

- Jargon without explanation (MFA, phishing, social engineering are fine to use but should be explained on first use in a course aimed at general employees, not assumed knowledge)
- Guilt or fear-based framing ("If you fail to follow these steps, you could be responsible for a breach")
- Content that could double as a how-to guide for an actual attacker — training should explain how to *recognise* a technique, not provide a step-by-step blueprint for executing one
