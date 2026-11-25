# Product Requirements Document (PRD): EduPlatform

## 1. Executive Summary
**EduPlatform** is a desktop-first web application designed to provide an integrated, interactive learning and assessment experience for high school and vocational students (e.g., XI PPLG). The platform combines adaptive quizzing, real-time AI tutoring, detailed post-quiz evaluations, community-sourced question banks, and 1-on-1 live mentor consultations into a cohesive educational ecosystem.

---

## 2. Target Audience & User Persona
- **Primary Persona:** Active Student (e.g., *Satrio Ernesto*, Grade 11 Software Engineering / PPLG student).
- **Goals:** Prepare for exams and competencies, track learning progress, get immediate conceptual help when stuck on a problem without being handed the answer, and consult with verified mentors when needed.
- **Pain Points:** Fragmented study tools, lack of immediate feedback during practice, and difficulty finding verified, structured practice questions.

---

## 3. Core Architecture & User Flow (9 Screens)
The application uses a persistent **App Shell** featuring a left navigation sidebar and a top utility bar (search, 7-day streak indicator, notifications, and student profile).

1. **Dashboard UI (`1. Dashboard UI`)**
   - **Purpose:** Central hub for student activity and quick access.
   - **Key Features:** Greeting header, KPI summary cards (completed quizzes, average score, study hours, class rank), active quiz resume banner, recent subjects/quizzes grid, 5-minute daily challenge widget, weekly activity chart, upcoming exam schedule, and weekly leaderboard.
2. **Quiz Directory (`2. Halaman Daftar Kuis`)**
   - **Purpose:** Searchable and filterable catalog of available quizzes.
   - **Key Features:** Progress summary header (18/34 completed, 84.6% avg accuracy), search bar, category pills, difficulty & status filters, quiz cards with metadata (question count, duration, rating), bookmark/delete actions, and primary **Mulai / Play** CTA.
3. **Student Profile (`3. Halaman Profil`)**
   - **Purpose:** Manage academic identity, preferences, and account security.
   - **Key Features:** Profile banner with stats (87.5% avg, 42 modules, Top 5%), tabbed navigation (Personal Info, Security, Notifications, Learning History), editable academic form (Full Name, NISN, Class/Major, School, Topic Interests, Bio), account verification status (6/6), achievements, and homeroom teacher contact.
4. **Quiz Overview / Intro (`4. Beranda Kuis`)**
   - **Purpose:** Pre-quiz briefing and readiness check.
   - **Key Features:** Module metadata (duration, question count, passing grade, remaining attempts), circular target accuracy gauge (92%), previous attempt history, exam rules & AI proctor notice, and primary **MULAI KUIS** CTA.
5. **Quiz Question Screen (`5. Halaman Pertanyaan Kuis`)**
   - **Purpose:** Distraction-free interactive exam interface.
   - **Key Features:** Progress bar, countdown timer, "Mark as Doubtful" toggle, question stem with code/pseudocode blocks and memory visualizations, multiple-choice options (A–E), question navigator drawer (1–25), and **Tanya AI Tutor** shortcut.
6. **AI Tutor Popup (`6. Popup AI Tutor - Tanya AI`)**
   - **Purpose:** Contextual, Socratic AI assistance triggered when a student answers incorrectly or requests help.
   - **Key Features:** Slide-over/modal drawer over the dimmed quiz screen, contextual problem awareness, visual concept breakdown (e.g., hash collision diagram), key hints without revealing the final answer, suggested follow-up prompts, and interactive chat input.
7. **Detailed Answer & Evaluation (`7. Penjelasan Detail Soal & Jawaban`)**
   - **Purpose:** Post-quiz review and conceptual reinforcement.
   - **Key Features:** Score summary (92/100, 23 correct, 2 wrong, 38 mins), filter tabs (All, Wrong Only, Correct Only), per-question breakdown comparing user answer vs. official key, deep-dive explanations with trap analysis and pseudocode, topic mastery bars, and PDF export.
8. **Community Question Bank (`8. Cari Soal dari Komunitas`)**
   - **Purpose:** Open repository for student- and teacher-contributed practice problems.
   - **Key Features:** Searchable repository, subject/level/sort filters, **Create & Share Question** CTA, community question cards with verification badges, upvotes, comment counts, trending topics sidebar, and top contributors leaderboard.
9. **Find a Mentor (`9. Cari Mentor / Guru - Call ur Mentor!`)**
   - **Purpose:** Directory for 1-on-1 live video/audio tutoring and career consultation.
   - **Key Features:** Search & filter bar (category, price range, availability), mentor cards with verified badges, ratings, specializations, hourly rates, and **Call Mentor** / chat / schedule actions, plus an upcoming sessions widget and mentorship FAQ.

---

## 4. Design System & Visual Guidelines
- **Design System:** *Modern Scholastic*
- **Color Palette:** Clean light mode (`#f8f9ff` surface, `#ffffff` elevated cards) anchored by primary scholastic blue (`#2563eb`), semantic greens for mastery/correctness, and warm amber/red for alerts and errors.
- **Typography:** `Plus Jakarta Sans` for crisp UI legibility, paired with monospace fonts for code snippets and pseudocode.
- **Layout & Components:** 8px border radius (`ROUND_EIGHT`), consistent card elevation, clear visual hierarchy, and persistent sidebar navigation across all authenticated views.

---

## 5. Success Metrics
- **Engagement:** Daily active streak retention and weekly study hours.
- **Learning Efficacy:** Improvement in average quiz scores across retakes and reduction in repeat conceptual errors after using *Tanya AI*.
- **Community & Mentorship:** Number of community questions attempted/upvoted and successful 1-on-1 mentor call bookings.