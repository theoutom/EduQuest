---
name: Modern Scholastic
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#434655'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#737686'
  outline-variant: '#c3c6d7'
  surface-tint: '#0053db'
  primary: '#004ac6'
  on-primary: '#ffffff'
  primary-container: '#2563eb'
  on-primary-container: '#eeefff'
  inverse-primary: '#b4c5ff'
  secondary: '#565e74'
  on-secondary: '#ffffff'
  secondary-container: '#dae2fd'
  on-secondary-container: '#5c647a'
  tertiary: '#006242'
  on-tertiary: '#ffffff'
  tertiary-container: '#007d55'
  on-tertiary-container: '#bdffdb'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#dae2fd'
  secondary-fixed-dim: '#bec6e0'
  on-secondary-fixed: '#131b2e'
  on-secondary-fixed-variant: '#3f465c'
  tertiary-fixed: '#6ffbbe'
  tertiary-fixed-dim: '#4edea3'
  on-tertiary-fixed: '#002113'
  on-tertiary-fixed-variant: '#005236'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '800'
    lineHeight: 56px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '800'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-xl:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-xl-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 26px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.015em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 30px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
  body-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: Inter
    fontSize: 10px
    fontWeight: '700'
    lineHeight: 14px
    letterSpacing: 0.04em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-desktop: 2rem
  margin: 1rem
  margin-desktop: 2.5rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
---

## Brand & Style

This design system embodies an intentional, focused, and elevated learning environment. Built for learners, educators, and institutions who demand professional clarity without clinical sterile boredom, the visual direction merges crisp academic authority with digital vitality.

### Brand Personality
- **Focused Clarity:** Reduces cognitive load to keep knowledge retention and deep work at the center.
- **Empowering & Optimistic:** Celebrates milestones and progress through purposeful micro-moments and rewarding accent tones.
- **Systematic & Institutional:** Grounded in collegiate structure, conveying rigor, dependability, and data integrity.

### Design Movement
- **Modern Corporate / Clean Functionalism:** High-legibility surfaces, crisp structure, intentional card architecture, and subtle layered depth. Flat, distraction-free content planes paired with deliberate, high-contrast feedback states make navigation intuitive across desktop productivity environments.

## Colors

The palette balances classical scholastic gravitas with dynamic digital UI feedback.

### Palette Hierarchy & Intent
- **Primary (`#2563EB` - Royal Indigo/Blue):** Drives principal focus states, primary CTA buttons, active navigation markers, and linear progress trajectories.
- **Secondary (`#0F172A` - Deep Educational Navy):** Forms structural framework pillars, desktop sidebar surfaces, high-contrast headings, and grounding focal points.
- **Tertiary (`#10B981` - Mint Green):** Reserved exclusively for validation triumphs, accurate quiz responses, streak counts, and completed curriculum tracks.
- **Accent Warning/AI (`#F59E0B` - Amber Coral):** Serves contextual AI tutor interactions, real-time feedback suggestions, pending assignment alerts, and time-sensitive reminders.
- **Neutral (`#64748B` - Slate Gray):** Handles subheadings, unselected control outlines, passive labels, and neutral icon fills.

### Surface Tones
- **Canvas Base:** `#F8FAFC` provides a calm, glare-free working background for long study sessions.
- **Surface Elevation (Cards/Panels):** `#FFFFFF` delivers crisp contrast against the base canvas.
- **Dividers & Strokes:** `#E2E8F0` defines structural boundaries without visual noise.

## Typography

The typographic pairing balances structure with accessibility.

- **Headlines (Plus Jakarta Sans):** Selected for its friendly geometry, open apertures, and crisp readability at display scales. Provides optimism to academic dashboards and track headers.
- **Body & Labels (Inter):** Employs tall x-heights and neutral grotesk forms to sustain readability across dense academic prose, complex mathematical expressions, and intricate course tables.
- **Tabular Figures:** All analytical readouts, test clocks, percentage scores, and grade markers enforce monospace/tabular numbers (`tnum`) to preserve layout alignment across state shifts.

## Layout & Spacing

A structured desktop-first shell prioritizing split-pane education views, fixed productivity sidebars, and comfortable reading tracks.

### Layout Mechanics
- **Desktop Grid:** 12-column layout with 32px gutters and 40px outer canvas margins. Total container width caps at 1440px to prevent lecture and reading widths from stretching beyond comfortable eye tracking spans (optimal line length: 65–75 characters).
- **Desktop App Shell:**
  - **Left Navigation Rail:** Persistent 260px desktop sidebar expanding to 280px on ultrawide viewports.
  - **Primary Work Surface:** Dynamic fluid area consuming remaining horizontal breadth with internal section divisions.
  - **Contextual Side Inspector:** Optional 360px collapsible right utility pane reserved for AI tutoring, note-taking, or lesson outlines.
- **Tablet / Mobile Adaptation:**
  - **Tablet (768px – 1024px):** 8-column layout, 24px gutters, navigation collapses to an icon-only dock or bottom sheet.
  - **Mobile (< 768px):** 4-column layout, 16px gutters, 16px margins. Full stacking for cards, step questions, and data tables.

## Elevation & Depth

Visual hierarchy uses low-contrast borders alongside diffused, non-distracting ambient shadows. Depth serves to separate workspaces and direct attention without flashy skeuomorphism.

### Depth Scales
- **Level 0 (Flat Canvas):** `#F8FAFC`. Base backdrop across background views.
- **Level 1 (Card Surfaces & Modules):** `#FFFFFF` surface with a crisp `1px solid #E2E8F0` border and a delicate drop shadow: `0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05)`.
- **Level 2 (Hovered Cards & Action Elements):** Elevated `1px solid #CBD5E1` border paired with `0 10px 15px -3px rgba(15, 23, 42, 0.06), 0 4px 6px -4px rgba(15, 23, 42, 0.04)`.
- **Level 3 (Modals, AI Assistant Drawers & Dropdowns):** `#FFFFFF` with `0 20px 25px -5px rgba(15, 23, 42, 0.10), 0 8px 10px -6px rgba(15, 23, 42, 0.04)`.
- **Level 4 (Toasts & Urgent Question Prompts):** `#FFFFFF` paired with an accent-tinted border (`#2563EB` or `#10B981`) and deep diffusion: `0 25px 50px -12px rgba(15, 23, 42, 0.18)`.

## Shapes

The interface implements a balanced geometric radius (`roundedness: 2` / 8px base) that strikes an approachable yet disciplined tone.

- **Base Radius (`0.5rem` / `8px`):** Standard for form inputs, interactive lesson buttons, quiz options, code blocks, and contextual popovers.
- **Large Radius (`1rem` / `16px`):** Used for analytical cards, video/lecture player containers, module accordions, and modular exam surfaces.
- **Full Pill (`9999px`):** Reserved for system badges, category status tags, user progression levels, and streak indicators.

## Components

### Buttons
- **Primary:** Filled `#2563EB` background with `#FFFFFF` text. Solid 8px radius. Active state darkens to `#1D4ED8`. Elevation shifts subtly from Level 1 to Level 2 on hover.
- **Secondary / Ghost:** `#FFFFFF` background with a `1px solid #E2E8F0` border and `#0F172A` text. Hover transitions to `#F1F5F9`.
- **Success (Quiz Submit / Verified):** `#10B981` background with white text, utilized only during terminal confirmation steps.

### Badges & Chips
- **Category Chips:** Light background (`#EFF6FF`), text `#1D4ED8`, fully rounded (pill), 4px vertical / 10px horizontal padding.
- **Status Badges:** Small dot indicator paired with medium label typography:
  - *Success (Mastered / Correct):* Background `#ECFDF5`, text `#065F46`, dot `#10B981`.
  - *AI Tip / Review:* Background `#FFFBEB`, text `#92400E`, dot `#F59E0B`.
  - *In Progress:* Background `#F1F5F9`, text `#334155`, dot `#64748B`.

### Cards & Lesson Containers
- Pure white `#FFFFFF` surface bounded by a continuous `1px solid #E2E8F0` border.
- 16px corner radius with an internal padding rhythm of 24px (`space-lg`).
- Interactive assessment cards reveal a `2px solid #2563EB` accent border upon active selection.

### Form Inputs & Answer Fields
- Default state uses a `1px solid #CBD5E1` boundary on white backgrounds.
- Focus state switches the border to `#2563EB` with a subtle outer glow: `0 0 0 3px rgba(37, 99, 235, 0.15)`.
- Validated correct input displays a `#10B981` border with a pale green trailing checkmark icon.

### Selection Controls (Checkboxes & Radios)
- Distinct 8px radius for checkboxes, full circle for radios.
- Unselected: `1.5px solid #94A3B8`.
- Selected: `#2563EB` fill with a sharp white checkmark or center pip.
- Correct Review State: `#10B981` fill with an animated reveal.

### Persistent Desktop Sidebar
- `#0F172A` background with `#F8FAFC` headline typography and `#94A3B8` icon links.
- Active route item: `#1E293B` background highlight with a left inset indicator bar (`3px solid #2563EB`) and `#FFFFFF` text.

### AI Study Companion Drawer
- Subtle `#F8FAFC` elevated container with an amber accent top-border (`#F59E0B`).
- Clean conversational bubbles alternating between slate-tinted queries and white response cards with actionable practice prompts.