# JARVIS — AI-Powered WordPress Assistant

## What Is JARVIS?

JARVIS is an autonomous AI agent that **operates** WordPress from natural language. It lives inside the Gutenberg editor sidebar and on every wp-admin page. You type what you want — it builds, configures, optimizes, and manages your entire WordPress site. No page builders. No code. Just conversation.

---

## Core Capabilities at a Glance

### 1. AI Page Builder (Design Intelligence)
- **95 curated section patterns** across 24 categories (heroes, features, pricing, testimonials, CTAs, galleries, footers, and more)
- **17 full-page blueprints** — SaaS, agency, restaurant, real estate, eCommerce, fitness, consulting, education, nonprofit, and more
- **One-shot page building** — `build_from_blueprint` creates a complete multi-section page in a single command
- **70+ animation classes** — scroll-triggered entrances, 3D transforms, glass effects, parallax, marquee, aurora backgrounds, gradient text
- **16 design themes** — Modern Dark, Clean White, Ocean Breeze, Luxury Gold, Emerald Night, Cosmic Purple, and more
- **8 industry profiles** — AI automatically picks the right theme, section flow, and effects for your business type
- **150-entry autocorrect engine** — silently fixes AI-invented CSS class names to valid `wpa-*` classes

### 2. Reference Site Analyzer
- Paste any URL → JARVIS extracts colors, fonts, section structure, and layout
- **Smart color clustering** — automatically classifies colors into primary, accent, dark/light backgrounds, text colors
- **Font hierarchy detection** — identifies heading vs body fonts
- **Section classification** — identifies hero, features, pricing, testimonials, CTA sections
- **Pattern suggestions** — recommends matching patterns from the library for each detected section

### 3. Content Management (16 actions)
- Create, edit, delete, clone, search posts and pages
- Read and manipulate block structures surgically
- Bulk edit, bulk find-and-replace across the entire site
- Import content from CSV/JSON, manage revisions
- AI content generation — blog posts, product descriptions, emails, social posts, ad copy

### 4. Media Intelligence
- Search the media library by keyword
- Import images from any external URL
- AI image generation from text prompts
- Set featured images

### 5. Full Site Administration (29 actions)
- Update site settings, permalinks, rewrite rules
- Install, activate, deactivate, update, delete plugins
- Install, search, activate, delete themes
- Create and manage users, roles, sessions
- Navigation menus, widgets, shortcodes
- WP-Cron job management
- Database optimization (revisions, transients, orphaned meta, spam cleanup)
- URL redirects with 301/302 support
- Full site export (WXR)
- Debug log reader (read, search, tail, clear)

### 6. SEO & Performance (6 actions)
- SEO meta management — title, description, Open Graph, canonical (Yoast, RankMath, SEOPress compatible)
- Sitemap generation with search engine ping
- Accessibility auditor — alt text, heading structure, ARIA, contrast, tab order
- Performance analyzer — image optimization, render-blocking detection
- Live web search for research and competitor analysis
- URL content extraction

### 7. WooCommerce (8 actions, auto-detected)
- Products, orders, coupons, categories, shipping, settings, analytics, inventory
- Full store setup from conversation

### 8. AI Workflow Engine
- **Plan-Confirm-Execute** — presents a numbered plan before destructive actions, waits for approval
- **Streaming responses** — real-time token streaming for long operations
- **Checkpoint & undo** — every modifying action creates a snapshot; one-command rollback
- **Persistent memory** — remembers your brand, preferences, and decisions across sessions
- **A/B testing** — create content variants, track impressions/conversions, declare winners
- **Scheduled automation** — recurring AI-driven task chains on cron

### 9. Multi-Surface UI
- **Editor Sidebar** — Gutenberg PluginSidebar for page building
- **Admin Drawer** — floating chat on every wp-admin screen
- **Voice Input** — microphone dictation
- **Conversation History** — searchable across sessions
- **Usage Dashboard** — token/API usage tracking

### 10. Ecosystem Integrations
- **BYOK (Bring Your Own Key)** — any AI provider via OpenRouter
- **WordPress Abilities API** (WP 6.9+) — JARVIS actions as first-class WordPress abilities
- **MCP Server** — Model Context Protocol for external AI tool interop
- **Multi-page site generator** — complete website with navigation in one command

---

## Quick Stats

| Metric | Count |
|--------|-------|
| Total AI actions | 76 |
| Section patterns | 95 |
| Full-page blueprints | 17 |
| Animation classes | 70+ |
| Design themes | 16 |
| Industry profiles | 8 |
| Pattern categories | 24 |

---

## 11 Production-Grade Demo Prompts (Page Building & Design)

These are ultra-detailed, copy-paste-ready prompts. Each one is a complete creative brief with exact design system specs, color codes, typography scales, spacing values, animation choreography, and layout architecture. They are designed to push the AI to produce cinema-quality, production-ready pages.

---

### Prompt 1: Transformers Universe — Dark Cinematic Franchise Launch

```
Act like an elite AI-native web design studio composed of a senior cinematic web designer, award-winning UX strategist, premium brand copywriter, motion designer, and senior front-end engineer.

Your goal is to design and generate a professional-grade, modern, deployable landing page that feels like a seamless cinematic experience — not a traditional "stacked sections" website.

Task: Create a sleek, futuristic, high-converting Transformers landing page that feels like one continuous immersive scroll journey with zero visual gaps between sections.

Follow this exact step-by-step execution framework:

STEP 1 — DEFINE THE DESIGN SYSTEM

Color palette (strict — no deviations):
- Base background: deep charcoal #0a0a0a graduating to true black #050505
- Section alternation: #0a0a0a → #0d0d0d → #0a0a0a → #111111 (subtle shifts, never white)
- Primary accent: electric blue #3b82f6
- Secondary accent: metallic silver #94a3b8
- Danger/Decepticon accent: neon red #ef4444
- Autobot highlight: #60a5fa (lighter blue for faction-specific moments)
- Text primary: #f1f5f9 (95% white — never pure #ffffff)
- Text muted: #64748b
- Text accent: #93c5fd (blue-tinted for links/highlights)
- Card surfaces: rgba(255,255,255,0.04) with 1px border rgba(255,255,255,0.08)
- Gradient overlays: linear-gradient(180deg, rgba(10,10,10,0.9) 0%, rgba(10,10,10,0.4) 50%, rgba(10,10,10,0.95) 100%)

Typography scale (8pt grid, tight cinematic feel):
- Display / Hero H1: 64–72px, weight 800, letter-spacing -0.04em, line-height 1.05
- Section H2: 42–48px, weight 700, letter-spacing -0.02em, line-height 1.15
- Card H3: 22–24px, weight 600, letter-spacing -0.01em, line-height 1.3
- Body copy: 17–18px, weight 400, line-height 1.7, max-width 680px per paragraph
- Overline labels: 13px, weight 600, letter-spacing 0.1em, uppercase, color #64748b
- Button text: 15px, weight 600, letter-spacing 0.02em

Spacing system:
- Section vertical padding: 100px top / 100px bottom (hero: 120px top / 100px bottom)
- Section horizontal padding: 40px (constrained inner max-width 1200px)
- Card internal padding: 32px
- Card gap: 28px
- Element gap within cards: 12px
- H2 to first paragraph: 20px
- Paragraph to paragraph: 16px
- Section to section: 0px (seamless — use gradient transitions, never hard edges)
- Button padding: 16px 40px, border-radius 100px

Animation choreography (max 5 animated sections — restraint is premium):
- Hero: wpa-aurora background + wpa-noise overlay. NO scroll animations on hero text (above fold).
- Autobot cards: wpa-stagger-children on columns parent, wpa-glass + wpa-lift on each card
- Timeline: wpa-fade-up on each timeline block with wpa-delay-100, wpa-delay-200, wpa-delay-300
- Gallery: wpa-stagger-left on the gallery grid
- Stats: wpa-scale-up on the stats section wrapper
- CTA button: wpa-glow on the primary button
- Do NOT animate Decepticons section, franchise overview, or Allspark section. Stillness = power.

STEP 2 — ARCHITECT THE 10-SECTION SCROLL EXPERIENCE

Each section must visually blend into the next. No hard separators. Use background color graduation (#0a0a0a → #0d0d0d → #0a0a0a) so the page reads as one continuous dark canvas. Alternate high-impact visual sections with immersive storytelling sections. Escalate intensity toward the CTA.

Section 1 — HERO (Cinematic Entrance)
- Purpose: Instant emotional hook. Establish franchise gravitas in 3 seconds.
- Layout: core/cover, align full, minHeight 100vh, customOverlayColor #0a0a0a at 85% opacity.
- Background: wpa-aurora + wpa-noise classes on the cover block. The aurora creates slow-moving indigo/cyan gradient blobs behind the text.
- Content (centered, max-width 800px):
  - Overline: "THE TRANSFORMERS UNIVERSE" — 13px, uppercase, #64748b, letter-spacing 0.1em
  - H1: "More Than Meets the Eye" — 72px, weight 800, className wpa-gradient-text (renders as indigo-to-violet gradient). Letter-spacing -0.04em, line-height 1.05.
  - Paragraph: "For forty years, the war between Autobots and Decepticons has raged across galaxies, generations, and every screen imaginable. From the streets of Cybertron to the skyline of Earth — this is the saga that defined a universe." — 18px, #94a3b8, max-width 640px, centered.
  - Two buttons side by side:
    - "Explore the Universe" — bg #3b82f6, text white, border-radius 100px, padding 16px 40px, className wpa-glow
    - "Meet the Characters" — transparent bg, 1px solid rgba(255,255,255,0.25), text #e2e8f0, border-radius 100px, padding 16px 40px

Section 2 — FRANCHISE OVERVIEW (Storytelling Depth)
- Purpose: Establish historical credibility. Build emotional investment.
- Layout: core/group (align full, bg #0d0d0d, padding 100px 40px) → constrained inner → core/columns (2 columns, 60/40 split).
- Left column:
  - Overline: "LEGACY" — 13px, uppercase, #3b82f6, letter-spacing 0.1em
  - H2: "A Legacy Spanning Generations" — 44px, weight 700, #f1f5f9
  - Paragraph 1: The origin story — Hasbro's 1984 toy line, the partnership with Takara Tomy, how a simple transforming robot toy became a cultural phenomenon. How the animated series gave machines personality, honor, and moral complexity.
  - Paragraph 2: The cinematic era — Michael Bay's explosive 2007 reimagining. $4.8 billion in global box office across seven films. The shift from Saturday morning cartoons to ILM-rendered spectacle. How Bumblebee (2018) proved the franchise could do heart alongside scale.
  - Paragraph 3: The modern renaissance — Transformers One (2024), the animated origin story. The expanding TV universe, gaming franchise, and comic book lore. A franchise that reinvents itself every decade.
  - All paragraphs: 17px, #94a3b8, line-height 1.7
- Right column: Search the media library for a Transformers image. Full-height, object-fit cover, border-radius 12px.
- Animation: None. Let the story breathe.

Section 3 — AUTOBOTS SPOTLIGHT (Faction Showcase)
- Purpose: Emotional connection with heroes. Character-driven engagement.
- Layout: core/group (align full, bg #0a0a0a, padding 100px 40px) → constrained inner.
- Header (centered):
  - Overline: "FACTION: AUTOBOTS" — 13px, uppercase, #60a5fa
  - H2: "Defenders of Freedom" — 46px, weight 700, #f1f5f9, centered
  - Paragraph: "Led by Optimus Prime, the Autobots fight not for conquest, but for the right of all sentient beings to live free. Their courage is forged in the fires of Cybertron." — 17px, #94a3b8, centered, max-width 600px
- Cards: core/columns (3 columns, className wpa-stagger-children, gap 28px). Each column:
  - core/group (className "wpa-glass wpa-lift", padding 32px, border-radius 16px)
  - Character image from media library (border-radius 8px, aspect-ratio 4:3)
  - H3: character name — 24px, weight 600, #f1f5f9
  - Role line: "Autobot Leader" / "Scout" / "Chief Medical Officer" — 14px, #3b82f6, uppercase, letter-spacing 0.05em
  - Quote: Cinematic one-liner in italics — 16px, #94a3b8
  - Characters: Optimus Prime ("Freedom is the right of all sentient beings."), Bumblebee ("Small in stature. Limitless in heart."), Ratchet ("I've patched up warriors who couldn't patch up themselves.")

Section 4 — DECEPTICONS SPOTLIGHT (Faction Contrast)
- Purpose: Tension. Every great story needs a villain worth fearing.
- Layout: Identical structure to Autobots but with red accent system.
- Header:
  - Overline: "FACTION: DECEPTICONS" — 13px, uppercase, #ef4444
  - H2: "Conquerors of Worlds" — 46px, weight 700, #f1f5f9, centered
  - Paragraph: "The Decepticons believe in one truth: power is the only currency that matters. Under Megatron's iron will, they have crushed worlds, shattered alliances, and brought Cybertron to its knees." — 17px, #94a3b8, centered
- Cards: core/columns (3 columns, gap 28px). Each column:
  - core/group (padding 32px, border-radius 16px, bg rgba(239,68,68,0.06), border 1px solid rgba(239,68,68,0.15))
  - No wpa-glass (use solid dark-red-tinted cards instead for faction contrast)
  - Card hover: wpa-lift class
  - Characters: Megatron ("Peace through tyranny."), Starscream ("Every leader falls. I merely hasten the inevitable."), Soundwave ("Information is the ultimate weapon. Silence is its delivery system.")
- Animation: None. Stillness communicates menace.

Section 5 — THE ALLSPARK & CYBERTRON (Mythology)
- Purpose: World-building. Make the audience feel the stakes.
- Layout: core/group (align full, bg #111111, padding 100px 40px) → constrained inner → core/media-text (media on right, 50/50 split).
- Text side:
  - Overline: "MYTHOLOGY" — 13px, uppercase, #3b82f6
  - H2: "The AllSpark — Origin of All Life" — 44px, weight 700, #f1f5f9
  - Paragraph 1: Write about Cybertron — the metallic planet, living architecture, the golden age before war. A civilization millions of years old, powered by Energon, governed by the Primes.
  - Paragraph 2: The AllSpark itself — the cosmic artifact capable of creating new Transformer life. Why both factions would destroy everything to possess it. The tragedy of a war fought over the power of creation.
  - Paragraph 3: Earth's role — why this backwater organic planet became the war's final theater. How humanity was caught between gods of metal.
- Media side: Search media library for Cybertron/AllSpark imagery. Dark gradient overlay on image: linear-gradient(90deg, #111111 0%, transparent 30%).
- Animation: None.

Section 6 — TIMELINE (Historical Arc)
- Purpose: Narrative momentum. Show the scope of the war.
- Layout: core/group (align full, bg #0a0a0a, padding 100px 40px) → constrained inner.
- Header (centered):
  - Overline: "CHRONOLOGY" — 13px, uppercase, #64748b
  - H2: "The War Across Time" — 44px, weight 700, #f1f5f9
- Timeline: Use alternating two-column layout. Left-aligned event, right-aligned description (alternating). Each event block:
  - core/group (padding 24px 0, border-left 2px solid rgba(59,130,246,0.3))
  - Year/era label: 16px, weight 700, #3b82f6
  - Event title: 22px, weight 600, #f1f5f9
  - Description: 16px, #94a3b8, 2–3 sentences
- Five events:
  1. "The Fall of Cybertron" — The civil war that shattered a golden age. Megatron's uprising, the corruption of the Senate, the planet's slow death.
  2. "Arrival on Earth (1984)" — The Ark crashes. Autobots and Decepticons awaken on a primitive organic world. The secret war begins.
  3. "Battle of Mission City (2007)" — The war goes public. Megatron and Optimus clash in downtown Los Angeles. Humanity learns it is not alone.
  4. "Age of Extinction" — Governments turn on the Autobots. Lockdown hunts Optimus. The line between protector and fugitive blurs.
  5. "Rise of the Beasts (2023)" — The Maximals arrive. Unicron threatens Earth. A new generation of Transformers joins the fight.
- Animation: wpa-fade-up on each timeline block, staggered with wpa-delay-100 through wpa-delay-500.

Section 7 — TECHNOLOGY & TRANSFORMATION (Bento Grid)
- Purpose: Intellectual fascination. Showcase the "science" of the fiction.
- Layout: core/group (align full, bg #0d0d0d, padding 100px 40px) → constrained inner.
- Header (centered):
  - Overline: "TECHNOLOGY" — 13px, uppercase, #3b82f6
  - H2: "Engineering Beyond Imagination" — 44px, weight 700, #f1f5f9
- Grid: core/columns with className wpa-bento-grid (automatic 12-col grid: first child spans 8, second spans 4, third spans 6, fourth spans 6). Four blocks:
  - Block 1 (large): "Energon" — The lifeblood of Cybertron. Liquid energy that powers every Transformer. Rare, volatile, and the ultimate prize in the war. Write 3 sentences. Bg rgba(59,130,246,0.06), border 1px solid rgba(59,130,246,0.12), padding 40px, border-radius 16px.
  - Block 2 (small): "T-Cog Mechanics" — The biomechanical organ that enables transformation. How a 30-foot robot reconfigures into a sports car in 0.4 seconds. 2 sentences.
  - Block 3 (medium): "Space Bridge" — Wormhole technology that connects Cybertron to any point in the galaxy. The strategic weapon that controls the war's geography. 2 sentences.
  - Block 4 (medium): "Cybertronian Weaponry" — Ion blasters, Energon swords, fusion cannons. Weapons forged from the planet itself. 2 sentences.
- Animation: wpa-fade-up on the entire grid wrapper.

Section 8 — FAN CULTURE (Gallery + Community)
- Purpose: Social proof. Connect the fictional universe to real-world passion.
- Layout: core/group (align full, bg #0a0a0a, padding 100px 40px) → constrained inner.
- Header (centered):
  - H2: "A Global Community" — 44px, weight 700, #f1f5f9
  - Paragraph: "From BotCon to TFCon, from hand-painted garage kits to million-dollar collections — the Transformers fandom is one of the most passionate communities on Earth. Four decades of shared stories, fan art, and the eternal debate: Optimus or Megatron?" — 17px, #94a3b8, centered, max-width 640px
- Gallery: core/gallery with 4 images from media library (search for Transformers, robots, action figures, convention). Columns: 4. Gap: 16px. Each image: border-radius 8px.
- Animation: wpa-stagger-left on the gallery.

Section 9 — BY THE NUMBERS (Stats)
- Purpose: Scale and legacy. Quantify the franchise's impact.
- Layout: core/group (align full, bg #3b82f6, padding 80px 40px) → constrained inner → core/columns (4 columns, centered text).
- Each column:
  - Number: 48px, weight 800, #ffffff, centered
  - Label: 14px, weight 600, rgba(255,255,255,0.8), uppercase, letter-spacing 0.06em, centered
- Stats:
  - "40+" / "Years of Storytelling"
  - "7" / "Blockbuster Films"
  - "$4.8B+" / "Global Box Office"
  - "1B+" / "Toys Sold Worldwide"
- Animation: wpa-scale-up on the section wrapper.

Section 10 — FINAL CTA (Conversion Climax)
- Purpose: Convert emotional investment into action.
- Layout: core/group (align full, gradient "linear-gradient(135deg, #0a0a0a 0%, #1e3a5f 50%, #3b82f6 100%)", padding 120px 40px, centered text).
- Content:
  - H2: "Join the Fight" — 48px, weight 700, #ffffff
  - Paragraph: "The war between Autobots and Decepticons has defined generations. Now it's your turn. Choose your side. Explore the lore. Join a community that spans the globe." — 18px, rgba(255,255,255,0.85), max-width 580px
  - Button: "Enter the Universe" — bg #ffffff, text #0a0a0a, weight 600, border-radius 100px, padding 18px 48px, className wpa-glow
- Animation: wpa-glow on the button only.

STEP 3 — CONTENT & COPY RULES
- Write original, cinematic, production-ready copy for every section. No placeholder text. No "lorem ipsum." No "[insert copy here]."
- Tone: Authoritative, reverent, cinematic. Like the narrator of a documentary trailer.
- Every paragraph must have real substance — historical facts, character motivations, world-building details.
- Search the media library for Transformers-related images. Use real images, not placeholders.

STEP 4 — TECHNICAL CONSTRAINTS
- Use dark page mode (body class wpa-page-dark, background #0a0a0a).
- All sections align full. All inner content constrained to 1200px max-width.
- Zero margin between sections. Seamless dark canvas.
- Follow the 60-30-10 color rule: 60% dark background, 30% card surfaces, 10% blue accent.
- Maximum 5 animated sections. Restraint is premium.
```

---

### Prompt 2: SaaS Product — "Flowmark" (Stripe/Linear Aesthetic)

```
Act like an elite SaaS design team: a senior product designer who has shipped for Stripe, Linear, and Vercel; a conversion-focused copywriter who writes for $100M ARR products; and a motion designer who understands that restraint is what separates premium from gimmicky.

Task: Build a complete 8-section landing page for "Flowmark" — an AI-powered project management tool. This page must feel like it belongs next to Linear.app and Stripe.com. Dark, precise, engineered.

DESIGN SYSTEM

Color palette (Modern Dark theme — strict):
- Background base: #0c0c14 (dark navy-black, NOT pure black)
- Surface / card bg: #111827 (slightly lighter for depth)
- Elevated surface: rgba(255,255,255,0.06) with 1px border rgba(255,255,255,0.08)
- Primary: #6366f1 (indigo — buttons, links, highlights)
- Primary hover: #818cf8 (lighter indigo)
- Accent: #06b6d4 (cyan — secondary highlights, step numbers, badges)
- Text primary: #f9fafb (near-white)
- Text secondary: #94a3b8 (muted silver)
- Text tertiary: #64748b (dimmed labels)
- Gradient CTA: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a78bfa 100%)
- Gradient text: linear-gradient(135deg, #e0e7ff 0%, #818cf8 40%, #6366f1 100%)
- Card glass: backdrop-filter blur(20px) saturate(180%), bg rgba(255,255,255,0.06), border 1px solid rgba(255,255,255,0.1), border-radius 16px

Typography:
- Hero H1: 60–68px, weight 800, letter-spacing -0.04em, line-height 1.08
- Section H2: 40–46px, weight 700, letter-spacing -0.02em, line-height 1.15
- Card H3: 20–22px, weight 600, line-height 1.3
- Body: 17px, weight 400, line-height 1.7, color #94a3b8
- Overline: 12–13px, weight 600, uppercase, letter-spacing 0.1em, color #6366f1
- Button: 15px, weight 600, letter-spacing 0.02em
- Pricing number: 48px, weight 800, color #f9fafb
- Stats number: 44px, weight 800, color #f9fafb

Spacing:
- Section padding: 100px vertical, 40px horizontal. Inner constrained to 1200px.
- Card padding: 32px. Card gap: 24px. Card border-radius: 16px.
- H2 margin-bottom: 20px. Overline margin-bottom: 12px.
- Button: padding 16px 40px, border-radius 100px.
- Between sections: 0px (seamless dark canvas).

Animation choreography (max 5 animated sections):
- Hero: wpa-aurora background + wpa-noise texture. No scroll animations on hero (above fold).
- Features: wpa-stagger-children on the bento grid parent. wpa-glass + wpa-lift on each card.
- Stats: wpa-scale-up on the stats section.
- Testimonials: wpa-fade-up with wpa-delay-200.
- CTA button: wpa-glow.
- Logo bar: wpa-marquee for continuous scroll.
- Pricing, How It Works: NO animations. Clean and static = trustworthy.

SECTION ARCHITECTURE

Section 1 — HERO (First Impression)
- Purpose: Communicate the value proposition in under 5 seconds. Establish premium positioning.
- Layout: core/cover (align full, minHeight 100vh, customOverlayColor #0c0c14, className "wpa-aurora wpa-noise").
- Content (centered, max-width 720px):
  - Overline: "AI-POWERED PROJECT MANAGEMENT" — 13px, #6366f1, uppercase
  - H1: "Project Management, Redesigned by AI" — 64px, weight 800, className wpa-gradient-text, letter-spacing -0.04em
  - Paragraph: "Flowmark uses machine intelligence to auto-prioritize your backlog, predict bottlenecks before they happen, and keep your entire team shipping 2x faster. No more Monday morning sprint planning marathons." — 18px, #94a3b8, max-width 620px
  - Two buttons:
    - "Start Free Trial" — bg #6366f1, text white, border-radius 100px, padding 16px 44px, className wpa-glow
    - "Watch Demo" — transparent bg, border 1px solid rgba(255,255,255,0.2), text #e2e8f0, border-radius 100px

Section 2 — LOGO BAR (Social Proof)
- Purpose: Instant credibility. Reduce "who uses this?" anxiety.
- Layout: core/group (align full, bg #0c0c14, padding 48px 40px, border-top 1px solid rgba(255,255,255,0.06)).
- Content:
  - Paragraph (centered): "Trusted by 2,000+ engineering teams worldwide" — 14px, #64748b, uppercase, letter-spacing 0.06em
  - Logo row: core/columns (6 columns, className wpa-marquee). Use text placeholders styled as company names: "Vercel", "Stripe", "Notion", "Figma", "Linear", "Datadog" — each 16px, weight 600, #64748b, centered. The marquee creates infinite horizontal scroll.

Section 3 — FEATURES (Bento Grid)
- Purpose: Show the product's core capabilities with visual hierarchy.
- Layout: core/group (align full, bg #0c0c14, padding 100px 40px) → constrained inner.
- Header (centered):
  - Overline: "CAPABILITIES" — 13px, #6366f1
  - H2: "Everything Your Team Needs to Ship" — 44px, #f9fafb
  - Paragraph: "Four AI-powered modules that replace six separate tools in your stack." — 17px, #94a3b8, max-width 540px
- Bento grid: core/columns (className "wpa-bento-grid wpa-stagger-children"). 4 feature cards:
  - Card 1 (spans 8 cols — large): "AI Sprint Planning" — "Flowmark analyzes your team's velocity, current capacity, and dependency graph to auto-generate sprint plans that actually make sense. No more 2-hour grooming sessions." Bg rgba(99,102,241,0.08), border 1px solid rgba(99,102,241,0.15), padding 40px.
  - Card 2 (spans 4 cols — small): "Smart Dependencies" — "See every blocker before it blocks. Flowmark maps cross-team dependencies in real-time and alerts you 48 hours before a collision." className wpa-glass wpa-lift.
  - Card 3 (spans 6 cols): "Real-time Dashboards" — "Live project health at a glance. Burndown, velocity, cycle time, and deployment frequency — all updating in real time, no manual tracking." className wpa-glass wpa-lift.
  - Card 4 (spans 6 cols): "Automated Standups" — "AI summarizes what happened yesterday, what's planned today, and what's blocked — delivered to Slack at 9am. Your team gets 15 minutes back every morning." className wpa-glass wpa-lift.

Section 4 — HOW IT WORKS (Process)
- Purpose: Reduce complexity. Make adoption feel effortless.
- Layout: core/group (align full, bg #111827, padding 100px 40px) → constrained inner.
- Header (centered):
  - Overline: "HOW IT WORKS" — 13px, #06b6d4
  - H2: "Three Steps to Faster Shipping" — 44px, #f9fafb
- 3 columns (gap 32px). Each column:
  - Step number: 48px, weight 800, #06b6d4 (cyan accent)
  - H3: step title — 22px, weight 600, #f9fafb
  - Paragraph: 2 sentences — 16px, #94a3b8
  - Steps:
    1. "Connect Your Tools" — "Plug in GitHub, Jira, Linear, Slack, and Notion in under 2 minutes. Flowmark reads your existing workflow — no migration, no data entry."
    2. "AI Learns Your Patterns" — "Within one sprint cycle, Flowmark understands your team's velocity, bottlenecks, and communication patterns. It builds a model unique to how you work."
    3. "Ship on Autopilot" — "Sprint planning, standups, blockers, and reporting happen automatically. You focus on building. Flowmark handles the project management."
- Animation: None. Process sections should feel simple and clear.

Section 5 — STATS (Proof of Scale)
- Purpose: Quantify credibility. Numbers override skepticism.
- Layout: core/group (align full, bg #0c0c14, padding 80px 40px, className wpa-scale-up) → constrained inner → core/columns (4 columns).
- Each column (centered):
  - Number: 44px, weight 800, #f9fafb
  - Label: 14px, weight 500, #64748b, uppercase, letter-spacing 0.06em
- Stats:
  - "47%" / "Faster Sprint Delivery"
  - "2.3M" / "Tasks Automated"
  - "99.9%" / "Platform Uptime"
  - "4.9/5" / "Average User Rating"

Section 6 — TESTIMONIALS (Voice of the Customer)
- Purpose: Let real users sell the product for you.
- Layout: core/group (align full, bg #0d0d0f, padding 100px 40px, className "wpa-fade-up wpa-delay-200") → constrained inner.
- Header (centered):
  - H2: "Loved by Engineering Teams" — 44px, #f9fafb
- 3 columns (gap 28px). Each card:
  - core/group (className "wpa-glass-light" for light frosted glass, padding 32px, border-radius 16px)
  - Quote: 16px, #e2e8f0, italic, line-height 1.7. Write real-sounding, specific quotes (mention features by name, cite metrics).
  - Name: 15px, weight 600, #f9fafb
  - Role + Company: 14px, #64748b
  - Testimonials:
    1. "Flowmark cut our sprint planning from 3 hours to 15 minutes. The AI dependency mapping alone saved us from two production incidents last quarter." — Sarah Chen, CTO, Amplitude
    2. "I was skeptical about AI project management until Flowmark predicted a launch delay two weeks before anyone on the team saw it coming. We rerouted resources and shipped on time." — Marcus Rivera, Product Manager, Lattice
    3. "Our engineers stopped dreading standups. Flowmark's automated summaries are more accurate than what humans were writing anyway. We gained back 5 hours per week across the team." — Priya Sharma, Engineering Lead, Retool

Section 7 — PRICING (Conversion)
- Purpose: Remove price objection. Make Pro tier the obvious choice.
- Layout: core/group (align full, bg #0c0c14, padding 100px 40px) → constrained inner.
- Header (centered):
  - H2: "Simple, Transparent Pricing" — 44px, #f9fafb
  - Paragraph: "Start free. Upgrade when your team grows." — 17px, #94a3b8
- 3 columns (gap 24px). Each pricing card:
  - core/group (padding 40px, border-radius 16px, bg #111827, border 1px solid rgba(255,255,255,0.08))
  - Tier 1 — "Starter": $0/mo, "For individuals and small side projects." Features: 1 project, basic sprint planning, manual standups, community support.
  - Tier 2 — "Pro" (HIGHLIGHTED): $12/user/mo, "For teams that ship fast." Features: unlimited projects, AI sprint planning, smart dependencies, automated standups, real-time dashboards, priority support. Card has border 2px solid #6366f1, badge "Most Popular" in small indigo pill at top.
  - Tier 3 — "Enterprise": "Custom", "For organizations at scale." Features: everything in Pro, SSO/SAML, custom integrations, SLA guarantee, dedicated success manager.
  - Each card has a CTA button at bottom: "Get Started" (Starter/Pro) or "Contact Sales" (Enterprise).
- Animation: None. Pricing must feel trustworthy and static.

Section 8 — CTA (Final Conversion)
- Purpose: Last chance to convert. High-contrast, impossible to miss.
- Layout: core/group (align full, gradient "linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a78bfa 100%)", padding 120px 40px, centered).
- Content:
  - H2: "Ready to Ship Faster?" — 48px, weight 700, #ffffff
  - Paragraph: "Join 2,000+ teams who replaced spreadsheets, standups, and status meetings with one AI-powered workspace. Free forever for small teams." — 18px, rgba(255,255,255,0.9), max-width 560px
  - Button: "Start Free — No Credit Card" — bg #ffffff, text #6366f1, weight 600, border-radius 100px, padding 18px 48px, className wpa-glow

CONTENT RULES
- Write specific, metric-driven SaaS copy. Every feature claim has a number or outcome.
- No generic phrases like "powerful tool" or "next-generation platform."
- Testimonials must name real-sounding people with specific job titles and company names.
- Use dark page mode (wpa-page-dark). Seamless sections, zero gaps.
```

---

### Prompt 3: Nike-Inspired Athletic Brand — "VELO Athletics" (Reference Analyzer Demo)

```
Act like a senior brand designer who has worked with Nike, Adidas, and On Running, paired with a performance copywriter who writes for athlete-focused DTC brands and a UX engineer who builds conversion-optimized product launch pages.

IMPORTANT: First, use analyze_reference_site on https://www.nike.com to extract their exact color palette, typography hierarchy, section structure, and design language. Apply the extracted colors and section flow as the foundation for this page.

Task: Build an 8-section product launch landing page for "VELO Athletics" — a premium running shoe brand launching the Ultraknit 3 racing flat. This page must channel Nike's bold, minimal, athlete-first energy — large imagery, massive typography, and absolute product confidence.

DESIGN SYSTEM (apply analyzer colors as overrides)

Base palette (use until analyzer results override):
- Background: #0a0a0a (true dark — Nike uses near-black for product pages)
- Surface: #141414 (slightly lifted sections)
- Text primary: #ffffff (Nike uses pure white on dark)
- Text secondary: #a1a1aa (muted descriptions)
- Accent: use whatever primary color the analyzer extracts from Nike (likely black/white/red)
- Product highlight: #ef4444 (performance red for VELO branding)
- Success/metric: #22c55e (green for positive stats)

Typography (Nike-inspired — bold, oversized, minimal):
- Hero H1: 72–80px, weight 900 (extra bold), letter-spacing -0.05em, line-height 1.0, uppercase
- Section H2: 48–56px, weight 800, letter-spacing -0.03em, line-height 1.1, uppercase
- Product specs: 14px, weight 600, uppercase, letter-spacing 0.12em, color #a1a1aa
- Body: 17px, weight 400, line-height 1.7
- Stat numbers: 56px, weight 900, color #ffffff
- Button: 14px, weight 700, uppercase, letter-spacing 0.08em

Spacing:
- Section padding: 100px vertical (hero: 140px top). Horizontal: 40px. Inner max-width 1200px.
- Card padding: 28px. Card gap: 24px.
- Between H2 and body: 16px. Between sections: 0px (seamless).
- Button: padding 18px 48px, border-radius 0px (squared — Nike aesthetic, not rounded).

Animation (restrained, performance-focused):
- Hero: NO animations (above fold — product must load instantly)
- Tech grid: wpa-stagger-children on the bento grid
- Stats: wpa-scale-up on the section
- Gallery: wpa-stagger-left
- Comparison: wpa-fade-up
- No glass effects. Nike is solid surfaces, not frosted glass.

SECTION ARCHITECTURE

Section 1 — HERO (Product Statement)
- Purpose: Instant desire. The shoe IS the page.
- Layout: core/cover (align full, minHeight 100vh, customOverlayColor #0a0a0a at 70% opacity).
- Search media library for a running/athletic shoe image as background.
- Content (centered, max-width 680px):
  - Overline: "VELO ATHLETICS — ULTRAKNIT 3" — 13px, weight 700, #a1a1aa, uppercase, letter-spacing 0.15em
  - H1: "ENGINEERED TO FLY" — 76px, weight 900, uppercase, #ffffff, letter-spacing -0.05em, line-height 1.0. No gradient — pure white, Nike-style. Let the size do the talking.
  - Paragraph: "37% lighter than the competition. 12% more energy return. The VELO Ultraknit 3 isn't an evolution — it's a new species of racing flat. Built for the runners who refuse to settle." — 18px, #a1a1aa, line-height 1.7
  - Two buttons (squared, side by side):
    - "SHOP NOW" — bg #ffffff, text #0a0a0a, weight 700, uppercase, padding 18px 48px, border-radius 0
    - "EXPLORE THE TECH" — bg transparent, border 2px solid #ffffff, text #ffffff, weight 700, uppercase, padding 18px 48px, border-radius 0

Section 2 — PRODUCT SHOWCASE (Split Detail)
- Purpose: Let the product specs build rational desire alongside emotional pull.
- Layout: core/group (align full, bg #0a0a0a, padding 100px 40px) → constrained inner → core/media-text (60% text, 40% media).
- Text side:
  - Overline: "PERFORMANCE SPECS" — 13px, #ef4444, uppercase, letter-spacing 0.12em
  - H2: "BUILT FOR YOUR FASTEST MILE" — 48px, weight 800, uppercase, #ffffff
  - Spec list (each spec as its own line, not bullet points):
    - "WEIGHT: 184g" — 14px, weight 600, #a1a1aa, uppercase, letter-spacing 0.1em
    - "DROP: 7mm heel-to-toe"
    - "MIDSOLE: ReactFoam 3.0 — 12% more energy return than EVA"
    - "PLATE: Full-length CarbonPlate propulsion system"
    - "UPPER: AeroWeave mesh — 40% more breathable"
    - "OUTSOLE: DuraGrip rubber — 300+ mile durability"
  - Paragraph: "Every gram was questioned. Every compound was tested. The Ultraknit 3 is 18 months of obsessive engineering distilled into the most efficient racing flat we've ever built." — 17px, #a1a1aa
- Media side: Search media library for shoe/product image. Full bleed, no border-radius (squared aesthetic). Dark background behind product.

Section 3 — TECHNOLOGY (Bento Grid)
- Purpose: Deep dive into engineering. Build "I need this" conviction.
- Layout: core/group (align full, bg #141414, padding 100px 40px) → constrained inner.
- Header:
  - Overline: "TECHNOLOGY" — 13px, #ef4444, uppercase
  - H2: "THE SCIENCE OF SPEED" — 48px, weight 800, uppercase, #ffffff
- Bento grid (className "wpa-bento-grid wpa-stagger-children"). 4 tech cards:
  - Card 1 (large — spans 8): "AEROWEAVE UPPER" — "Engineered mesh with variable density zones. Tighter weave at the midfoot for lockdown. Open structure at the forefoot for ventilation. 40% more breathable than knit uppers. 22% lighter than the Ultraknit 2. Your foot breathes. Your shoe disappears." Bg #1a1a1a, padding 40px, border-radius 0 (squared).
  - Card 2 (small — spans 4): "REACTFOAM 3.0" — "Nitrogen-infused foam compound. Absorbs impact at heel strike, returns energy at toe-off. 12% more energy return than standard EVA. Tested across 50,000 km of road running."
  - Card 3 (spans 6): "CARBONPLATE" — "Full-length carbon fiber propulsion plate. Sits between two layers of ReactFoam. Creates a rolling motion that propels you forward with every stride. The same technology used by world-record holders."
  - Card 4 (spans 6): "DYNAMICFIT COLLAR" — "Adaptive ankle collar with internal heel counter. Locks your foot in place without pressure points. Zero break-in period. Lace up and go."
  - All cards: bg #1a1a1a, border 1px solid rgba(255,255,255,0.06), padding 36px.

Section 4 — ATHLETE SPOTLIGHT (Social Proof via Authority)
- Purpose: Transfer credibility from elite athletes to the product.
- Layout: core/group (align full, bg #0a0a0a, padding 100px 40px) → constrained inner.
- Header:
  - Overline: "ATHLETES" — 13px, #ef4444, uppercase
  - H2: "TRUSTED BY THE FASTEST" — 48px, weight 800, uppercase, #ffffff
- 3 columns (gap 28px). Each card:
  - core/group (bg #141414, padding 32px, border-radius 0)
  - Search media library for athlete/runner images
  - Name: 22px, weight 700, #ffffff, uppercase
  - Event: 14px, weight 600, #ef4444, uppercase, letter-spacing 0.08em (e.g., "MARATHON — 2:08:41")
  - Quote: 16px, #a1a1aa, italic, line-height 1.7
  - Athletes:
    1. Elena Vasquez / "MARATHON — 2:08:41" / "The Ultraknit 3 is the first shoe that feels faster at mile 24 than mile 1. That CarbonPlate rollover is something else entirely."
    2. James Okafor / "5K — 13:22" / "I've tested every carbon plate shoe on the market. The VELO is the only one that doesn't sacrifice ground feel for propulsion. I can feel the road. I can feel the speed."
    3. Aria Montez / "ULTRA TRAIL — UTMB FINISHER" / "184 grams and it survived 170km of Alpine terrain. The DuraGrip outsole is no joke. This shoe doesn't quit."
  - Animation: None. Athlete authority should feel solid, not flashy.

Section 5 — BY THE NUMBERS (Stats)
- Purpose: Quantify superiority. Numbers kill objections.
- Layout: core/group (align full, bg #141414, padding 80px 40px, className wpa-scale-up) → constrained inner → core/columns (4 columns, centered text).
- Each stat:
  - Number: 56px, weight 900, #ffffff
  - Label: 13px, weight 600, #a1a1aa, uppercase, letter-spacing 0.1em
- Stats:
  - "37%" / "Lighter Than Competition"
  - "12%" / "More Energy Return"
  - "50K+" / "Athletes Worldwide"
  - "4.9/5" / "Average Rating"

Section 6 — COMMUNITY (Gallery)
- Purpose: Social belonging. Running is a tribe.
- Layout: core/group (align full, bg #0a0a0a, padding 100px 40px) → constrained inner.
- Header:
  - H2: "JOIN THE MOVEMENT" — 48px, weight 800, uppercase, #ffffff, centered
  - Paragraph: "From 5K parkruns to ultramarathons. From treadmills to mountain trails. The VELO community spans 40 countries and one shared obsession: faster." — 17px, #a1a1aa, centered, max-width 580px
- Gallery: core/gallery (4 columns, gap 12px, className wpa-stagger-left). Search media library for running, athletes, fitness images. Each image: border-radius 0 (squared).

Section 7 — COMPARISON (Product vs Competitors)
- Purpose: Rational decision support. Remove "what about brand X?" doubt.
- Layout: core/group (align full, bg #141414, padding 100px 40px, className wpa-fade-up) → constrained inner.
- Header:
  - H2: "HOW VELO STACKS UP" — 44px, weight 800, uppercase, #ffffff, centered
- Comparison table (core/table or structured columns, 3 columns):
  - Column headers: "VELO ULTRAKNIT 3" (highlighted) / "GENERIC CARBON RACER" / "PREMIUM COMPETITOR"
  - Rows:
    1. Weight: "184g" / "220g" / "198g"
    2. Energy Return: "12% above EVA" / "6% above EVA" / "9% above EVA"
    3. Breathability: "AeroWeave mesh — 40% more airflow" / "Standard knit" / "Engineered mesh"
    4. Durability: "300+ miles (DuraGrip)" / "200 miles" / "250 miles"
    5. Price: "$179" / "$160" / "$250"
  - VELO column: text #ffffff, bg rgba(239,68,68,0.08), border-left 2px solid #ef4444. Others: text #a1a1aa.

Section 8 — CTA (Conversion)
- Purpose: Close the sale. Urgency + desire.
- Layout: core/group (align full, bg #0a0a0a, padding 120px 40px, centered).
- Content:
  - H2: "YOUR FASTEST MILE STARTS HERE" — 52px, weight 900, uppercase, #ffffff
  - Paragraph: "The VELO Ultraknit 3 is available now. Free shipping on all orders. 30-day no-questions-asked returns. If it doesn't make you faster, send it back." — 18px, #a1a1aa, max-width 560px
  - Button: "SHOP THE ULTRAKNIT 3 — $179" — bg #ffffff, text #0a0a0a, weight 700, uppercase, padding 20px 56px, border-radius 0, letter-spacing 0.06em

CONTENT RULES
- Tone: Confident, technical, athlete-focused. Short sentences. No fluff.
- Every feature claim has a specific number or measurable outcome.
- Uppercase headings throughout — this is sportswear, not SaaS.
- Dark page mode (wpa-page-dark). Zero gaps between sections.
- No glass effects, no aurora, no gradients. Solid, bold, Nike energy.
```

---

### Prompt 4 (BONUS): MotoGP-Inspired — "Apex Racing League" (Reference Analyzer Demo)

```
Act like a motorsport brand agency: a senior designer who has built campaigns for MotoGP, Formula 1, and Red Bull Racing; a sports copywriter who captures the adrenaline of race day in every sentence; and a motion designer who knows speed requires restraint on screen.

IMPORTANT: First, use analyze_reference_site on https://www.motogp.com to extract their exact color palette, typography, section structure, and visual energy. Apply the extracted colors as the primary palette for this page.

Task: Build an 8-section landing page for "Apex Racing League" — a fictional premier motorcycle racing championship. The page must feel like race day — high-octane, precise, dramatic. Think MotoGP.com meets Formula 1 broadcast graphics.

DESIGN SYSTEM (apply analyzer colors as overrides)

Color palette (Cyberpunk Neon theme base — override with analyzer results):
- Background: #09090b (near-black, like tarmac)
- Surface: #141416 (pit lane gray)
- Primary: #ef4444 (race red — expect analyzer to confirm something similar from MotoGP)
- Accent: #f59e0b (amber/yellow for timing highlights, champagne gold)
- Danger/speed: #f43f5e (hot pink-red for velocity moments)
- Text primary: #fafafa (pure white on dark)
- Text secondary: #a1a1aa (carbon gray)
- Border subtle: rgba(255,255,255,0.08)
- Card surface: rgba(255,255,255,0.04) border 1px solid rgba(255,255,255,0.08)
- Gradient hero: linear-gradient(180deg, rgba(9,9,11,0.3) 0%, rgba(9,9,11,0.95) 100%)

Typography (motorsport — bold, condensed, high-impact):
- Hero H1: 68–76px, weight 900, letter-spacing -0.04em, line-height 1.0, uppercase
- Section H2: 44–52px, weight 800, letter-spacing -0.03em, line-height 1.1, uppercase
- Card H3: 22px, weight 700, uppercase, letter-spacing 0.02em
- Rider number: 72px, weight 900, color rgba(255,255,255,0.06) (watermark behind name)
- Body: 17px, weight 400, line-height 1.7, #a1a1aa
- Overline: 12px, weight 700, uppercase, letter-spacing 0.15em
- Stat numbers: 52px, weight 900

Spacing:
- Section padding: 100px vertical (hero: 120px). Horizontal: 40px. Inner: 1200px.
- Card padding: 32px. Gap: 24px. Border-radius: 8px (slightly rounded, not fully squared).
- Buttons: padding 18px 44px, border-radius 4px (technical, not playful).
- Between sections: 0px.

Animation choreography:
- Hero: wpa-aurora (use red-tinted aurora for racing energy) + wpa-noise. No scroll animations on hero.
- Rider cards: wpa-stagger-children on columns, wpa-lift on each card.
- Stats: wpa-scale-up.
- Gallery: wpa-stagger-left.
- CTA button: wpa-glow.
- Tech section, Championship, Fan Zone: NO animations. Precision = speed.

SECTION ARCHITECTURE

Section 1 — HERO (Race Day Arrival)
- Purpose: Visceral excitement. The roar of engines before lights out.
- Layout: core/cover (align full, minHeight 100vh, customOverlayColor #09090b at 75% opacity, className "wpa-aurora wpa-noise").
- Search media library for motorcycle/racing imagery as background.
- Content (centered, max-width 740px):
  - Overline: "APEX RACING LEAGUE — 2025 SEASON" — 12px, weight 700, #ef4444, uppercase, letter-spacing 0.15em
  - H1: "WHERE LEGENDS ARE FORGED" — 72px, weight 900, uppercase, className wpa-gradient-text (renders red-to-amber gradient). Line-height 1.0, letter-spacing -0.04em.
  - Paragraph: "22 races. 12 countries. 24 riders. The world's fastest motorcycle championship returns with more speed, more rivalry, and more at stake than ever. From the sweeping curves of Mugello to the neon-lit streets of the Singapore Night Race — this is where legends are written in lap times." — 18px, #a1a1aa, max-width 640px
  - Two buttons:
    - "2025 RACE CALENDAR" — bg #ef4444, text white, weight 700, uppercase, padding 18px 44px, border-radius 4px, className wpa-glow
    - "WATCH HIGHLIGHTS" — bg transparent, border 2px solid rgba(255,255,255,0.3), text #fafafa, weight 700, uppercase, padding 18px 44px, border-radius 4px

Section 2 — THE CHAMPIONSHIP (Context)
- Purpose: Establish scale and legitimacy. This isn't amateur racing.
- Layout: core/group (align full, bg #09090b, padding 100px 40px) → constrained → core/media-text (55% text / 45% media).
- Text side:
  - Overline: "THE CHAMPIONSHIP" — 12px, #f59e0b, uppercase, letter-spacing 0.12em
  - H2: "THE PINNACLE OF TWO-WHEEL RACING" — 46px, weight 800, uppercase, #fafafa
  - Paragraph 1: "The Apex Racing League is the world's premier motorcycle racing series. 24 riders from 14 nations compete on purpose-built 1000cc prototype machines capable of 360 km/h. Every circuit is a test of human nerve, machine engineering, and split-second decision-making at the limit of physics."
  - Paragraph 2: "The 2025 season spans 22 races across 12 countries — from the historic banks of Silverstone to the high-altitude challenge of the Buddh International Circuit. Every race weekend features three practice sessions, qualifying, a sprint race, and the full-distance Grand Prix."
  - Paragraph 3: "With rivalries that span decades and technologies that push the boundaries of materials science, the Apex Racing League isn't just sport. It's engineering at 300 km/h."
  - All: 17px, #a1a1aa, line-height 1.7
- Media side: Search media library for motorcycle/racing image. Full-height, object-fit cover, border-radius 8px. Dark gradient overlay on left edge: linear-gradient(90deg, #09090b 0%, transparent 25%).

Section 3 — RIDER PROFILES (Star Power)
- Purpose: Characters create fans. Fans create viewership.
- Layout: core/group (align full, bg #141416, padding 100px 40px) → constrained.
- Header (centered):
  - Overline: "2025 RIDERS" — 12px, #ef4444, uppercase
  - H2: "THE FASTEST ON EARTH" — 48px, weight 800, uppercase, #fafafa
- 3 columns (className wpa-stagger-children, gap 28px). Each rider card:
  - core/group (bg rgba(255,255,255,0.04), border 1px solid rgba(255,255,255,0.08), padding 32px, border-radius 8px, className wpa-lift)
  - Search media library for rider/athlete images
  - Rider number (watermark): 72px, weight 900, rgba(255,255,255,0.04), positioned as background element
  - Name: 22px, weight 700, #fafafa, uppercase
  - Nationality + Team: 14px, weight 600, #ef4444, uppercase, letter-spacing 0.08em
  - Stats line: "5x World Champion" or "Rookie of the Year 2024" — 14px, #f59e0b
  - Bio: 2 sentences about racing style and personality — 16px, #a1a1aa
  - Riders:
    1. "#1 MARCO VALENTINI" / "ITALY — ROSSO CORSA RACING" / "5x World Champion" / "The most decorated rider in Apex history. Valentini's smooth, calculated riding style masks a ferocious competitiveness that has broken the hearts of challengers for eight consecutive seasons."
    2. "#7 KAI TANAKA" / "JAPAN — SAKURA MOTORSPORT" / "Rookie of the Year 2024" / "At 21, Tanaka is the youngest rider on the grid and the most fearless. His late-braking overtakes and rain-race mastery have already earned him the nickname 'The Typhoon.'"
    3. "#23 LUCAS FERREIRA" / "BRAZIL — VERDE RACING" / "Fan Favorite — 3x Race Winner" / "Ferreira rides with the passion of São Paulo and the precision of a surgeon. His battles with Valentini have become the defining rivalry of the modern era."

Section 4 — CIRCUITS (Gallery)
- Purpose: Visual spectacle. Tracks are characters in motorsport.
- Layout: core/group (align full, bg #09090b, padding 100px 40px) → constrained.
- Header (centered):
  - Overline: "2025 CALENDAR" — 12px, #f59e0b, uppercase
  - H2: "ICONIC CIRCUITS" — 48px, weight 800, uppercase, #fafafa
- Gallery: core/gallery (4 columns, gap 16px, className wpa-stagger-left). Search media library for racing/track/circuit images.
- Below gallery: 4 columns with track names centered under each image.
  - "SILVERSTONE" / "MUGELLO" / "SUZUKA" / "CIRCUIT OF THE AMERICAS"
  - Each: 14px, weight 700, #fafafa, uppercase, letter-spacing 0.1em

Section 5 — SEASON STATS (Scale)
- Purpose: Quantify the spectacle.
- Layout: core/group (align full, bg #ef4444, padding 80px 40px, className wpa-scale-up) → constrained → core/columns (4 columns, centered).
- Each stat:
  - Number: 52px, weight 900, #ffffff
  - Label: 13px, weight 600, rgba(255,255,255,0.85), uppercase, letter-spacing 0.08em
- Stats:
  - "22" / "Championship Races"
  - "12" / "Countries"
  - "24" / "World-Class Riders"
  - "350M+" / "Global Viewers"

Section 6 — TECHNOLOGY (Machine Specs)
- Purpose: Engineering porn. The bikes are as compelling as the riders.
- Layout: core/group (align full, bg #141416, padding 100px 40px) → constrained.
- Header:
  - Overline: "ENGINEERING" — 12px, #ef4444, uppercase
  - H2: "MACHINES AT THE LIMIT" — 48px, weight 800, uppercase, #fafafa
- 3 columns (gap 28px). Each:
  - core/group (bg #09090b, border 1px solid rgba(255,255,255,0.06), padding 36px, border-radius 8px)
  - H3: 20px, weight 700, #fafafa, uppercase
  - Body: 16px, #a1a1aa, 3 sentences
  - Specs:
    1. "1000CC V4 ENGINE" — "The heart of every Apex prototype is a 1000cc V4 powerplant producing 260+ horsepower. Rev limits exceed 17,000 RPM. The engine is a stressed member of the chassis, meaning the bike's frame literally bolts to the engine block. Every vibration, every harmonic is engineered."
    2. "SEAMLESS GEARBOX" — "Seamless-shift transmission technology allows riders to change gears in under 10 milliseconds without closing the throttle. Zero power interruption. Zero lost time. The difference between victory and defeat is often measured in thousandths of a second."
    3. "CARBON FIBER AERODYNAMICS" — "Winglets, fairing profiles, and ducting are designed in wind tunnels and refined with CFD simulation. At 300 km/h, aerodynamic downforce exceeds 40kg, pressing the bike into the tarmac through corners that would otherwise be impossible."

Section 7 — FAN ZONE (Emotional Connection)
- Purpose: Community belonging. Racing is tribal.
- Layout: core/group (align full, bg #09090b, padding 100px 40px) → constrained.
- Header (centered): H2: "THE GRID IS CALLING" — 44px, weight 800, #fafafa, uppercase
- 2 columns (gap 32px). Each:
  - core/group (bg #141416, padding 36px, border-radius 8px, border-left 3px solid #ef4444)
  - Quote: 17px, #e4e4e7, italic
  - Attribution: 14px, #a1a1aa
  - Quotes:
    1. "I've been to 14 Grands Prix across 6 countries. Nothing compares to the sound of 24 bikes screaming into Turn 1 at Mugello. The ground shakes. Your chest vibrates. It's not sport — it's a religious experience." — David K., Melbourne, Season Pass Holder since 2018
    2. "My daughter watched the Singapore Night Race on TV and said she wanted to be a rider. That's the power of this sport. It doesn't just entertain — it inspires the next generation." — Yuki Tanaka, Tokyo, Apex Racing Fan

Section 8 — CTA (Conversion)
- Purpose: Convert excitement into ticket sales.
- Layout: core/group (align full, gradient "linear-gradient(135deg, #09090b 0%, #450a0a 50%, #ef4444 100%)", padding 120px 40px, centered).
- Content:
  - H2: "BE PART OF THE GRID" — 52px, weight 900, uppercase, #ffffff
  - Paragraph: "The 2025 season starts March 2nd at Losail, Qatar. 22 races. 12 countries. One championship. Secure your Season Pass and never miss a moment of the fastest show on Earth." — 18px, rgba(255,255,255,0.9), max-width 580px
  - Button: "GET YOUR SEASON PASS" — bg #ffffff, text #09090b, weight 700, uppercase, padding 20px 52px, border-radius 4px, className wpa-glow

CONTENT RULES
- Write like a motorsport broadcaster — dramatic but precise. Short punchy sentences mixed with flowing narrative.
- Every rider, circuit, and tech spec must have specific, believable details.
- Uppercase headings throughout (motorsport aesthetic).
- Dark page mode (wpa-page-dark). Seamless sections.
```

---

### Prompt 5: Restaurant — "Ember & Oak" (Warm Terracotta Theme)

```
Act like an upscale hospitality brand agency: a senior designer who has created identities for Michelin-starred restaurants and boutique hotels; a food copywriter who writes for Bon Appetit, Eater, and Infatuation; and a photographer art director who understands warm lighting, shallow depth of field, and the romance of plated food.

Task: Build a 6-section landing page for "Ember & Oak" — an upscale wood-fired restaurant in Williamsburg, Brooklyn. The page must feel like stepping into a candlelit dining room — warm, intimate, sensory. Not sleek and techy. Not trendy and loud. Timeless.

DESIGN SYSTEM (Terracotta Earth theme)

Color palette:
- Background primary: #1a1410 (warm near-black — like charred oak)
- Background secondary: #231c15 (slightly warmer for alternating sections)
- Surface / card bg: #2a2218 (dark walnut, for cards)
- Primary: #c2703e (terracotta — the color of the wood-fired oven)
- Primary light: #d4885a (lighter terracotta for hover states)
- Accent: #d4a853 (gold — candlelight, brass fixtures, olive oil)
- Text primary: #f5f0e8 (warm cream — never pure white)
- Text secondary: #c4b8a4 (muted parchment)
- Text accent: #d4a853 (gold for highlights)
- Border: rgba(212,168,83,0.15) (subtle gold borders)
- Image overlay: linear-gradient(180deg, rgba(26,20,16,0.6) 0%, rgba(26,20,16,0.9) 100%)

Typography (warm, editorial, serif-inspired):
- Hero H1: 60–72px, weight 700, letter-spacing -0.02em, line-height 1.1 (use serif feel if possible — the heading should feel editorial, not corporate)
- Section H2: 38–44px, weight 700, letter-spacing -0.01em, line-height 1.2
- Card H3: 22–24px, weight 600, line-height 1.3
- Body: 17px, weight 400, line-height 1.8, color #c4b8a4 (generous line-height for readability and warmth)
- Menu item name: 20px, weight 600, #f5f0e8
- Menu price: 18px, weight 500, #d4a853 (gold)
- Overline: 13px, weight 600, uppercase, letter-spacing 0.12em, #d4a853
- Button: 15px, weight 600, letter-spacing 0.04em

Spacing (generous — luxury breathes):
- Section padding: 100px vertical (hero: 140px top / 100px bottom). Horizontal: 48px. Inner: 1100px (slightly narrower than tech sites — intimate).
- Card padding: 36px. Card gap: 32px. Card border-radius: 16px (rounded = warm).
- All border-radius: 12–16px (nothing squared — warmth comes from curves).
- Button: padding 16px 40px, border-radius 100px (pill shape).
- Between sections: 0px.
- Image border-radius: 12px.

Animation choreography (minimal — warmth, not flash):
- Gallery: wpa-stagger-children (gentle reveal)
- Testimonials: wpa-fade-up with wpa-delay-200
- All other sections: NO animations. Stillness = elegance.
- NEVER use glass effects, aurora, glow, or gradient text. Those are cold/tech aesthetics.
- NEVER use uppercase headings. This is a restaurant, not a sports brand.

SECTION ARCHITECTURE

Section 1 — HERO (First Impression)
- Purpose: Sensory invitation. The guest should smell the wood smoke and feel the warmth before reading a single word.
- Layout: core/cover (align full, minHeight 90vh, customOverlayColor #1a1410 at 65% opacity). Search media library for restaurant/food/fire/dining imagery.
- Content (centered, max-width 700px):
  - Overline: "Williamsburg, Brooklyn" — 13px, #d4a853, uppercase, letter-spacing 0.12em
  - H1: "Fire. Flavor. Craft." — 68px, weight 700, #f5f0e8, letter-spacing -0.02em, line-height 1.1. No gradient, no uppercase. Warm, understated, confident.
  - Paragraph: "Wood-fired cuisine in the heart of Brooklyn. Since 2016, Ember & Oak has been crafting dishes over open flame — sourcing from local farms, aging meats in-house, and baking bread in a 900-degree oven built by hand. Every plate tells a story. Every meal is an invitation to slow down." — 18px, #c4b8a4, line-height 1.8, max-width 600px
  - Button: "Reserve a Table" — bg #c2703e, text #f5f0e8, weight 600, border-radius 100px, padding 16px 44px. Hover: bg #d4885a.

Section 2 — THE STORY (About / Chef)
- Purpose: Personal connection. Restaurants are about people, not brands.
- Layout: core/group (align full, bg #1a1410, padding 100px 48px) → constrained → core/media-text (55% text / 45% media).
- Text side:
  - Overline: "Our Story" — 13px, #d4a853, letter-spacing 0.12em
  - H2: "Twenty Years of Fire and Patience" — 42px, weight 700, #f5f0e8
  - Paragraph 1: "Chef Maria Santos grew up in her grandmother's kitchen in Porto, Portugal, where everything was cooked over charcoal and seasoned with time. After two decades in New York's most demanding kitchens — from the line at Gramercy Tavern to running the pass at Estela — she built a restaurant around the principle that fire is the oldest and finest technology in cooking."
  - Paragraph 2: "Ember & Oak's wood-fired oven was built by hand over six months from reclaimed firebrick and Castile clay. It reaches 900 degrees at its core. At that heat, a Neapolitan-style pizza blisters in 90 seconds. A whole branzino crisps in four minutes. A bone-in ribeye develops a crust that no cast iron can match."
  - Paragraph 3: "Every ingredient is sourced within 150 miles. Greens from Sang Lee Farms on Long Island. Beef from Kinderhook Farm in the Hudson Valley. Flour from Farmer Ground in Ithaca. This isn't a farm-to-table slogan — it's a supply chain Maria built relationship by relationship over eight years."
  - All: 17px, #c4b8a4, line-height 1.8
- Media side: Search media library for chef/kitchen/restaurant interior image. Border-radius 12px, object-fit cover.

Section 3 — MENU HIGHLIGHTS (Signature Dishes)
- Purpose: Create specific cravings. The guest should be hungry after reading this.
- Layout: core/group (align full, bg #231c15, padding 100px 48px) → constrained.
- Header (centered):
  - Overline: "From the Hearth" — 13px, #d4a853
  - H2: "Signature Dishes" — 42px, weight 700, #f5f0e8
  - Paragraph: "A selection from our seasonally rotating menu. Every dish built around what's perfect this week — not what's convenient." — 17px, #c4b8a4, max-width 520px
- 3 columns (gap 32px). Each card:
  - core/group (bg #2a2218, border 1px solid rgba(212,168,83,0.12), padding 36px, border-radius 16px)
  - Search media library for food/dish images. Image at top, border-radius 12px, aspect-ratio 4:3.
  - Dish name: 22px, weight 600, #f5f0e8
  - Price: 18px, weight 500, #d4a853
  - Description: 16px, #c4b8a4, italic, line-height 1.7
  - Dishes:
    1. "Bone-In Ribeye" / "$52" / "Dry-aged 45 days at Kinderhook Farm. Seared over cherry wood at 800 degrees. Served with smoked bone marrow butter, charred broccolini, and a side of hand-cut fries cooked in beef tallow. The crust alone is worth the visit."
    2. "Wood-Fired Branzino" / "$38" / "Whole Mediterranean sea bass, scored and stuffed with preserved lemon, fresh oregano, and Calabrian chili. Roasted in the oven for four minutes until the skin shatters. Finished with extra-virgin olive oil from our partner grove in Puglia."
    3. "Truffle Burrata" / "$22" / "Handmade burrata from Di Palo's on Grand Street. Draped over grilled sourdough rubbed with roasted garlic. Shaved black truffle from Umbria. A drizzle of chestnut honey. Simple. Devastating."

Section 4 — GALLERY (Atmosphere)
- Purpose: Visual immersion. The ambiance sells as much as the food.
- Layout: core/group (align full, bg #1a1410, padding 100px 48px) → constrained.
- Gallery: core/gallery (4 columns, gap 16px, className wpa-stagger-children). Search media library for restaurant interior, plated food, bar, candlelight, kitchen action images. All images: border-radius 12px.
- No header needed — the images speak.

Section 5 — TESTIMONIALS (Social Proof)
- Purpose: Credible endorsement. Food critics and real diners.
- Layout: core/group (align full, bg #231c15, padding 100px 48px, className "wpa-fade-up wpa-delay-200") → constrained.
- Header (centered): H2: "What They're Saying" — 40px, weight 700, #f5f0e8
- 2 columns (gap 32px). Each:
  - core/group (bg #2a2218, border-left 3px solid #d4a853, padding 36px, border-radius 12px)
  - Quote: 18px, #f5f0e8, italic, line-height 1.8
  - Attribution: 15px, #c4b8a4, weight 500
  - Quotes:
    1. "The best wood-fired cooking in New York, and it's not close. Chef Santos has built something that feels both ancient and completely modern — a restaurant where the fire does the talking and every ingredient earns its place on the plate. The bone-in ribeye is a masterclass." — Pete Wells, The New York Times Food Section
    2. "We've been coming to Ember & Oak every Friday for three years. It's our place. The staff knows our names, the wine list is honest and interesting, and Maria's food gets better every season. Last week's wood-roasted carrots with harissa yogurt might be the best thing I've eaten this year." — Rachel & Tom S., Williamsburg regulars

Section 6 — CTA (Reservation)
- Purpose: Convert desire into a booking. Provide practical details alongside the emotional pull.
- Layout: core/group (align full, gradient "linear-gradient(135deg, #1a1410 0%, #2a1f14 50%, #3d2b1a 100%)", padding 120px 48px, centered).
- Content (max-width 600px, centered):
  - H2: "Your Table Awaits" — 44px, weight 700, #f5f0e8
  - Paragraph: "Reservations recommended, especially Thursday through Saturday. Walk-ins welcome at the bar and chef's counter. Private dining available for parties of 8–24." — 17px, #c4b8a4, line-height 1.8
  - Details block (centered, spaced):
    - "247 Bedford Avenue, Williamsburg, Brooklyn" — 15px, #f5f0e8
    - "Tuesday – Sunday, 5:30pm – 11:00pm" — 15px, #c4b8a4
    - "Closed Mondays" — 14px, #c4b8a4
    - "(718) 555-0142" — 15px, #d4a853
  - Button: "Reserve on Resy" — bg #d4a853, text #1a1410, weight 600, border-radius 100px, padding 18px 48px. Hover: bg #e0b865.

CONTENT RULES
- Tone: Warm, sensory, specific. Write like a food critic, not a marketer.
- Describe flavors, textures, and origins. Name specific farms, suppliers, and techniques.
- No tech buzzwords. No "innovative." No "curated experience." Just honest food storytelling.
- Use warm page mode. All colors warm. No blue, no purple, no cool tones anywhere.
- No glass effects, no aurora, no gradient text. Solid warm surfaces with subtle gold borders.
```

---

### Prompt 6: Real Estate — "Meridian Properties" (Luxury Sand Dune Theme)

```
Act like a luxury real estate branding agency: a senior designer who builds visual identities for Sotheby's International Realty and Douglas Elliman; a real estate copywriter who writes for Mansion Global and Robb Report; and a UX strategist who understands that property buyers scroll slowly, study details, and make million-dollar decisions based on trust.

Task: Build an 8-section landing page for "Meridian Properties" — a luxury real estate brokerage specializing in Miami waterfront and high-rise properties. The page must feel aspirational, trustworthy, and polished — like opening a leather-bound property brochure.

DESIGN SYSTEM (Sand Dune theme)

Color palette:
- Background primary: #fefce8 (warm off-white with yellow undertone — like sunlit limestone)
- Background secondary: #faf5e4 (slightly warmer for alternating sections)
- Surface / cards: #ffffff (clean white cards with subtle shadow)
- Dark bg (hero/CTA): #422006 (deep espresso brown)
- Primary: #a16207 (burnished gold — luxury without being flashy)
- Primary hover: #ca8a04 (brighter gold)
- Accent: #1e3a5f (deep navy — trust, authority, waterfront)
- Text dark: #422006 (warm dark brown for body text on light backgrounds)
- Text light: #fefce8 (cream for text on dark backgrounds)
- Text muted: #a8a29e (warm gray)
- Border: rgba(161,98,7,0.15) (subtle gold border)
- Card shadow: 0 4px 24px rgba(66,32,6,0.08), 0 1px 4px rgba(66,32,6,0.04)
- Image overlay (dark sections): linear-gradient(90deg, rgba(66,32,6,0.85) 0%, rgba(66,32,6,0.4) 60%, transparent 100%)

Typography:
- Hero H1: 56–64px, weight 700, letter-spacing -0.02em, line-height 1.1
- Section H2: 38–44px, weight 700, letter-spacing -0.01em, line-height 1.2
- Card H3: 22px, weight 600, line-height 1.3
- Body: 17px, weight 400, line-height 1.7, color #422006
- Property price: 28px, weight 700, #a16207
- Property details: 14px, weight 500, #a8a29e, letter-spacing 0.02em
- Overline: 13px, weight 600, uppercase, letter-spacing 0.12em, #a16207
- Button: 15px, weight 600, letter-spacing 0.03em

Spacing:
- Section padding: 100px vertical. Horizontal: 48px. Inner: 1180px.
- Card padding: 0 (image top) + 28px (content bottom). Card gap: 28px. Card border-radius: 12px.
- Button: padding 16px 40px, border-radius 8px (refined, not pill-shaped).
- Between sections: 0px.

Animation choreography (minimal — luxury is still):
- Listings cards: wpa-stagger-children
- Stats: wpa-fade-up
- No glass, aurora, glow, gradient text. Use box-shadow for depth, wpa-lift for card hover.
- Light page mode (wpa-page-light).

SECTION ARCHITECTURE

Section 1 — HERO (Aspiration)
- Purpose: Instant lifestyle aspiration. The viewer should picture themselves in this life.
- Layout: core/cover (align full, minHeight 85vh, customOverlayColor #422006 at 60% opacity). Search media library for luxury home, waterfront, Miami skyline, pool, architecture images.
- Content split layout — text left (60%), atmospheric right (40% — the image handles it).
  - Overline: "MIAMI LUXURY REAL ESTATE" — 13px, #fefce8 at 70% opacity, uppercase
  - H1: "Live Where the Ocean Meets the Skyline" — 60px, weight 700, #fefce8, letter-spacing -0.02em
  - Paragraph: "Meridian Properties represents Miami's most distinguished waterfront residences, penthouses, and estates. For over fifteen years, we've connected discerning buyers with properties that define a lifestyle — not just a location." — 18px, rgba(254,252,232,0.85), line-height 1.7, max-width 560px
  - Two buttons:
    - "View Featured Listings" — bg #a16207, text #fefce8, weight 600, padding 16px 40px, border-radius 8px
    - "Schedule a Consultation" — bg transparent, border 1px solid rgba(254,252,232,0.4), text #fefce8, padding 16px 40px, border-radius 8px

Section 2 — FEATURED LISTINGS (Product Showcase)
- Purpose: Concrete proof of portfolio quality. Specific properties build trust.
- Layout: core/group (align full, bg #fefce8, padding 100px 48px) → constrained.
- Header (centered):
  - Overline: "Featured Properties" — 13px, #a16207
  - H2: "Curated for the Exceptional" — 42px, weight 700, #422006
- 3 columns (gap 28px, className wpa-stagger-children). Each property card:
  - core/group (bg #ffffff, border-radius 12px, overflow hidden, box-shadow 0 4px 24px rgba(66,32,6,0.08), className wpa-lift)
  - Property image at top: search media library for luxury home/condo/penthouse. Aspect-ratio 16:10, object-fit cover.
  - Content below (padding 28px):
    - Address: 14px, weight 500, #a8a29e, uppercase, letter-spacing 0.04em
    - Price: 28px, weight 700, #a16207
    - Specs: "X Bed | X Bath | X,XXX SF" — 14px, weight 500, #a8a29e
    - Description: 1 sentence — 16px, #422006
  - Properties:
    1. "1000 Brickell Plaza, PH-4201" / "$8,750,000" / "4 Bed | 5 Bath | 4,200 SF" / "Full-floor penthouse with 270-degree Biscayne Bay views, private elevator, and 1,200 SF wraparound terrace."
    2. "42 Star Island Drive" / "$22,500,000" / "7 Bed | 9 Bath | 12,400 SF" / "Gated waterfront estate on Star Island with 200 feet of direct bay frontage, infinity pool, and private dock."
    3. "Residences at Four Seasons Surf Club, Unit 801" / "$6,200,000" / "3 Bed | 4 Bath | 3,100 SF" / "Corner unit with floor-to-ceiling ocean views. Full Four Seasons service including in-residence dining, spa, and concierge."

Section 3 — SERVICES (What We Do)
- Purpose: Scope of expertise. Position as full-service, not just a listing site.
- Layout: core/group (align full, bg #faf5e4, padding 100px 48px) → constrained.
- Header (centered):
  - Overline: "Our Services" — 13px, #a16207
  - H2: "Full-Service Luxury Real Estate" — 42px, weight 700, #422006
- 4 columns (gap 24px). Each:
  - core/group (bg #ffffff, padding 32px, border-radius 12px, box-shadow, border-top 3px solid #a16207)
  - H3: 20px, weight 600, #422006
  - Body: 16px, #78716c, 2 sentences
  - Services:
    1. "Buyer Representation" — "White-glove buyer advisory for primary residences, vacation homes, and investment properties. We preview every property before you do — you only see what's worth your time."
    2. "Seller Strategy" — "Custom marketing plans with professional staging, architectural photography, drone video, and targeted digital campaigns. Our average listing sells within 28 days at 98.5% of asking price."
    3. "Investment Consulting" — "Data-driven investment analysis for Miami's evolving market. Pre-construction opportunities, rental yield projections, 1031 exchanges, and portfolio diversification strategy."
    4. "Property Management" — "For owners who need hands-free oversight. Tenant placement, maintenance coordination, financial reporting, and concierge-level care for your property while you're away."

Section 4 — MARKET STATS (Credibility)
- Purpose: Data-driven authority. Position Meridian as market experts.
- Layout: core/group (align full, bg #422006, padding 80px 48px, className wpa-fade-up) → constrained → core/columns (4 columns, centered).
- Each stat:
  - Number: 44px, weight 700, #fefce8
  - Label: 13px, weight 500, rgba(254,252,232,0.7), uppercase, letter-spacing 0.08em
- Stats:
  - "$2.4M" / "Average Sale Price"
  - "31" / "Avg Days on Market"
  - "18.7%" / "YoY Price Growth"
  - "1,240" / "Active Luxury Listings"

Section 5 — TESTIMONIALS (Trust)
- Purpose: Social proof from real-sounding clients.
- Layout: core/group (align full, bg #fefce8, padding 100px 48px) → constrained.
- Header: H2: "What Our Clients Say" — 42px, weight 700, #422006, centered
- 3 columns (gap 28px). Each:
  - core/group (bg #ffffff, padding 32px, border-radius 12px, box-shadow, border-left 3px solid #a16207)
  - Quote: 16px, #422006, italic, line-height 1.7
  - Name: 15px, weight 600, #422006
  - Detail: 14px, #a8a29e
  - Testimonials:
    1. "Meridian found us a penthouse in Brickell that wasn't even publicly listed. The entire process — from first viewing to closing — took 22 days. In Miami's market, that kind of access and speed is invaluable." — Alexandra & David Chen, Purchased at One Thousand Museum
    2. "We've bought and sold three properties with Meridian over five years. They understand the Miami market at a level no other agency we've worked with can match. Their investment analysis on our Edgewater pre-construction purchase was spot-on — 34% appreciation in 18 months." — Marco Gutierrez, Investor
    3. "Selling our Star Island home was emotional and complex. Meridian handled everything with discretion, professionalism, and patience. They brought a qualified buyer within three weeks — and got us 102% of asking." — The Hartley Family, Coral Gables

Section 6 — THE TEAM (People)
- Purpose: Personal connection. Real estate is a relationship business.
- Layout: core/group (align full, bg #faf5e4, padding 100px 48px) → constrained.
- Header (centered):
  - Overline: "Our Team" — 13px, #a16207
  - H2: "Your Advisors" — 42px, weight 700, #422006
- 3 columns (gap 28px). Each:
  - Search media library for professional headshot/portrait images
  - Image: border-radius 12px, aspect-ratio 3:4
  - Name: 20px, weight 600, #422006
  - Title: 14px, weight 500, #a16207
  - Bio: 16px, #78716c, 2 sentences
  - Team:
    1. "Sofia Reyes" / "Founding Partner & Lead Broker" / "15 years in Miami luxury. $820M in career sales. Specializes in waterfront estates and new development pre-construction."
    2. "James Whitfield" / "Senior Associate — Investment Properties" / "Former Goldman Sachs analyst turned real estate advisor. Deep expertise in rental yield modeling and 1031 strategy."
    3. "Isabella Torres" / "Associate — Buyer Representation" / "Raised in Coral Gables with unmatched neighborhood knowledge. 98% client satisfaction rate across 140+ transactions."

Section 7 — NEIGHBORHOOD GUIDE (Local Expertise)
- Purpose: Demonstrate hyperlocal knowledge. Help buyers choose a neighborhood before a property.
- Layout: core/group (align full, bg #fefce8, padding 100px 48px) → constrained.
- Header (centered):
  - Overline: "Miami Neighborhoods" — 13px, #a16207
  - H2: "Know Where You Belong" — 42px, weight 700, #422006
- 3 columns (gap 28px). Each:
  - core/group (bg #ffffff, border-radius 12px, overflow hidden, box-shadow)
  - Search media library for neighborhood/city/Miami images. Image at top.
  - Content (padding 28px):
    - H3: neighborhood name — 22px, weight 600, #422006
    - Body: 3 sentences about lifestyle, price range, and who it's for — 16px, #78716c
  - Neighborhoods:
    1. "Brickell" / "Miami's financial district turned lifestyle destination. High-rise living with walkable restaurants, boutiques, and nightlife. Condos range from $500K to $15M. Perfect for young professionals and international buyers who want urban energy with waterfront views."
    2. "Coral Gables" / "Tree-lined streets, Mediterranean architecture, and some of Miami's best schools. Single-family homes start at $1.2M and estates can exceed $30M. The neighborhood of choice for families and executives who value privacy and space."
    3. "South Beach" / "Art Deco charm meets oceanfront luxury. From renovated Collins Avenue condos to ultra-exclusive Fisher Island. Prices range from $800K to $50M+. For those who want culture, nightlife, and sand between their toes."

Section 8 — CTA (Conversion)
- Purpose: Convert interest into a conversation.
- Layout: core/group (align full, bg #422006, padding 120px 48px, centered).
- Content:
  - H2: "Your Miami Story Starts Here" — 46px, weight 700, #fefce8
  - Paragraph: "Whether you're buying your first waterfront condo, selling a generational estate, or building a Miami investment portfolio — a confidential conversation costs nothing and could change everything." — 18px, rgba(254,252,232,0.85), max-width 560px, line-height 1.7
  - Button: "Schedule a Private Consultation" — bg #a16207, text #fefce8, weight 600, padding 18px 48px, border-radius 8px. Hover: bg #ca8a04.
  - Below button: "(305) 555-0199 — sofia@meridianproperties.com" — 14px, rgba(254,252,232,0.6)

CONTENT RULES
- Write specific Miami addresses, real neighborhoods, believable prices and specs.
- Tone: Sophisticated, knowledgeable, confident without being salesy.
- No tech jargon. No "innovative solutions." Real estate is personal — write accordingly.
- Light page mode (wpa-page-light). Warm palette throughout.
```

---

### Prompt 7: Portfolio — "Aura Studio" (Monochrome Editorial)

```
Act like a minimalist design director who has led creative at Pentagram, Collins, and ManvsMachine; a portfolio copywriter who knows that restraint communicates confidence; and a typographer who believes whitespace is the most powerful design element.

Task: Build a 5-section portfolio page for "Aura Studio" — a brand identity and web design agency. This page must embody radical minimalism. No decoration. No embellishment. Typography, whitespace, and the quality of the work are the only design elements. Think: if Dieter Rams designed a portfolio website.

DESIGN SYSTEM (Monochrome Editorial theme)

Color palette (black, white, one accent — nothing else):
- Background: #fafafa (near-white — not pure #ffffff, which feels sterile)
- Surface / cards: #ffffff (clean white with shadow for depth)
- Card hover surface: #f5f5f5 (subtle gray shift)
- Text primary: #171717 (near-black)
- Text secondary: #737373 (warm gray)
- Text tertiary: #a3a3a3 (light gray for labels)
- Accent (single — used sparingly): #6366f1 (indigo — appears only on links, hover states, and one CTA)
- Border: rgba(0,0,0,0.06)
- Card shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.03)
- Card hover shadow: 0 8px 32px rgba(0,0,0,0.08)

Typography (the hero of the page — every pixel matters):
- Hero H1: 72–84px, weight 800, letter-spacing -0.05em, line-height 1.0, color #171717
- Section H2: 40–48px, weight 700, letter-spacing -0.03em, line-height 1.15
- Card H3: 20–22px, weight 600, letter-spacing -0.01em
- Body: 17px, weight 400, line-height 1.7, color #737373
- Overline: 12px, weight 600, uppercase, letter-spacing 0.15em, color #a3a3a3
- Process step number: 64px, weight 800, color rgba(0,0,0,0.04) (watermark)
- Result metric: 16px, weight 600, #6366f1 (accent — the only color pop on the page)

Spacing (maximalist whitespace — the design breathes):
- Section padding: 120px vertical (hero: 160px top / 120px bottom). Horizontal: 48px. Inner: 1100px.
- Card padding: 0 (image) + 32px (content area). Card gap: 32px.
- Hero H1 margin-bottom: 28px. H2 margin-bottom: 24px.
- Between sections: 0px (seamless white canvas).
- Button: padding 16px 40px, border-radius 8px.

Animation choreography (absolute minimum — 2 animated elements max):
- Hero heading: wpa-text-reveal (text clips in from left to right)
- Portfolio cards: wpa-lift on hover (subtle lift + shadow increase)
- Everything else: NO animations. Minimalism means stillness.
- NEVER use glass, aurora, glow, gradient text, stagger, or bento grid. These are not minimal.

SECTION ARCHITECTURE

Section 1 — HERO (Statement)
- Purpose: Communicate creative confidence in as few words as possible.
- Layout: core/group (align full, bg #fafafa, padding 160px 48px top / 120px bottom) → constrained (max-width 1000px).
- Content (left-aligned — NOT centered. Minimal design is asymmetric):
  - H1: "We Design Brands That Move People" — 80px, weight 800, #171717, letter-spacing -0.05em, line-height 1.0, className wpa-text-reveal. This heading IS the hero. No image. No background. Just type.
  - Paragraph: "Aura Studio is a six-person brand identity and digital design practice based in Brooklyn. We work with founders, startups, and cultural institutions who need a visual language as ambitious as their mission." — 18px, #737373, line-height 1.8, max-width 560px. Left-aligned under the heading.
  - No buttons in the hero. Minimal pages don't push — they pull.

Section 2 — SELECTED WORK (Portfolio Grid)
- Purpose: The work IS the pitch. Four projects that show range and results.
- Layout: core/group (align full, bg #fafafa, padding 100px 48px) → constrained.
- Header:
  - Overline: "Selected Work" — 12px, #a3a3a3, uppercase, letter-spacing 0.15em
  - H2: "Four Projects. Four Industries. One Standard." — 44px, weight 700, #171717
- Grid: core/columns (2 columns, gap 32px). 4 project cards (2x2 grid):
  - Each card: core/group (bg #ffffff, border-radius 12px, overflow hidden, box-shadow, className wpa-lift)
  - Project image: search media library for design/branding/website images. Aspect-ratio 16:10, object-fit cover. No border-radius on image (it fills the card top edge-to-edge).
  - Content (padding 32px):
    - Client: 12px, weight 600, #a3a3a3, uppercase, letter-spacing 0.1em
    - Project name: 22px, weight 600, #171717
    - Description: 16px, #737373, 1 sentence
    - Result: 16px, weight 600, #6366f1 (accent color — draws the eye)
  - Projects:
    1. "VAULT FINANCIAL" / "Complete Brand Redesign" / "New visual identity, website, and product design system for a Series B fintech platform." / "3x conversion rate after launch"
    2. "BLOOM WELLNESS" / "App Design & Brand Identity" / "End-to-end design for a meditation and wellness app — from naming to App Store." / "200K downloads in first quarter"
    3. "MAISON NOIR" / "Fashion eCommerce Platform" / "A luxury fashion marketplace built for editorial storytelling and seamless checkout." / "42% increase in average order value"
    4. "KINDLING RESTAURANT GROUP" / "Restaurant Identity System" / "Visual identity spanning three restaurant concepts under one hospitality group." / "Named Best New Branding by Brand New 2024"

Section 3 — ABOUT (Philosophy)
- Purpose: Give the work context. Small team, big conviction.
- Layout: core/group (align full, bg #ffffff, padding 120px 48px) → constrained (max-width 800px — narrower for readability).
- Content (left-aligned):
  - Overline: "About" — 12px, #a3a3a3, uppercase
  - H2: "Small Team. Big Impact." — 44px, weight 700, #171717
  - Paragraph 1: "Aura Studio was founded in 2019 with a simple belief: design should make complex things feel simple, and invisible things feel tangible. We are six people — two brand designers, two digital designers, a strategist, and a developer. No account managers. No layers. You talk to the people making the work."
  - Paragraph 2: "We work with 6–8 clients per year. That's a deliberate constraint. Every project gets our full attention for 8–16 weeks. We don't do rush jobs. We don't do spec work. We don't compete on price. We compete on the work."
  - Paragraph 3: "Our clients include venture-backed startups, established cultural institutions, and founders who understand that brand is not a logo — it's the sum of every interaction a person has with your company."
  - All: 17px, #737373, line-height 1.8

Section 4 — PROCESS (How We Work)
- Purpose: Demystify the engagement. Reduce "what do I actually get?" anxiety.
- Layout: core/group (align full, bg #fafafa, padding 120px 48px) → constrained.
- Header:
  - Overline: "Process" — 12px, #a3a3a3, uppercase
  - H2: "Four Phases. No Surprises." — 44px, weight 700, #171717
- 4 columns (gap 28px). Each:
  - Step number (watermark): 64px, weight 800, rgba(0,0,0,0.04)
  - H3: phase name — 20px, weight 600, #171717
  - Body: 16px, #737373, 2 sentences, line-height 1.7
  - Phases:
    1. "01" / "Discovery" / "We interview your team, audit your competitors, and map your audience. The goal: understand the problem before we design anything. This phase takes 1–2 weeks."
    2. "02" / "Strategy" / "Brand positioning, messaging framework, and creative direction. We define the rules before we play. You approve the strategy before a single pixel is designed."
    3. "03" / "Design" / "Visual identity, digital design, and all deliverables. We work in focused sprints with weekly reviews. No big reveal — you see the work evolve in real time."
    4. "04" / "Launch" / "Asset delivery, developer handoff, and brand guidelines. We stay involved through launch and offer 30 days of post-launch support for any refinements."

Section 5 — CONTACT CTA
- Purpose: Convert interest into a conversation. Low friction.
- Layout: core/group (align full, bg #ffffff, padding 140px 48px, centered).
- Content (centered, max-width 600px):
  - H2: "Let's Talk" — 48px, weight 700, #171717
  - Paragraph: "We take on new projects quarterly. If you're building something that needs a visual identity worthy of the ambition behind it — we'd love to hear about it." — 17px, #737373, line-height 1.8
  - Button: "Start a Conversation" — bg #6366f1 (the single accent color, used only here), text white, weight 600, padding 16px 44px, border-radius 8px. Hover: bg #818cf8.
  - Below button: "hello@aurastudio.design" — 15px, #a3a3a3

CONTENT RULES
- Less is more. Every word must earn its place.
- No exclamation marks. No "passionate." No "innovative." No "synergy."
- Tone: Confident, understated, precise. Like a well-designed business card.
- Light page mode (wpa-page-light). White/near-white background throughout.
- The accent color (#6366f1) appears ONLY on result metrics and the CTA button. Nowhere else.
```

---

### Prompt 8: Fitness — "APEX Performance" (Emerald Night Theme)

```
Act like a fitness brand agency: a senior designer who has built identities for Equinox, Barry's, and CrossFit Games; a fitness copywriter who writes like a coach talks — direct, motivating, no fluff; and a conversion strategist who knows gym landing pages live and die by the free trial CTA.

Task: Build a 7-section landing page for "APEX Performance" — a high-end CrossFit and strength training gym in Austin, TX. The page must feel like walking into an elite training facility — dark, intense, purposeful. Not a corporate wellness center. Not a budget gym. A place where serious athletes train.

DESIGN SYSTEM (Emerald Night theme)

Color palette:
- Background primary: #022c22 (deep dark green — like a forest at midnight)
- Background secondary: #03301e (slightly lighter for alternation)
- Surface / cards: rgba(16,185,129,0.06) with border 1px solid rgba(16,185,129,0.12)
- Primary: #10b981 (bright emerald — energy, growth, go-signal)
- Primary hover: #34d399 (lighter emerald)
- Accent: #a78bfa (soft violet — used sparingly for contrast, badges, highlights)
- Text primary: #d1fae5 (minty white — complements green)
- Text secondary: #6ee7b7 (bright green for highlights)
- Text muted: #6b7280 (warm gray)
- Border: rgba(16,185,129,0.15)
- Gradient CTA: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%)
- Stats bg: #10b981 (solid emerald for high-impact stats bar)

Typography (bold, commanding, coach-voice):
- Hero H1: 68–76px, weight 900, letter-spacing -0.04em, line-height 1.0, uppercase
- Section H2: 44–52px, weight 800, letter-spacing -0.03em, line-height 1.1, uppercase
- Card H3: 22px, weight 700, uppercase, letter-spacing 0.02em
- Body: 17px, weight 400, line-height 1.7, color #6ee7b7
- Pricing number: 48px, weight 900, #d1fae5
- Stat numbers: 48px, weight 900, #ffffff
- Overline: 12px, weight 700, uppercase, letter-spacing 0.12em, #10b981
- Button: 15px, weight 700, uppercase, letter-spacing 0.06em

Spacing:
- Section padding: 100px vertical. Horizontal: 40px. Inner: 1200px.
- Card padding: 32px. Gap: 24px. Border-radius: 12px.
- Button: padding 18px 44px, border-radius 8px.
- Between sections: 0px.

Animation choreography:
- Programs: wpa-stagger-children on the 4-column grid. wpa-fade-up on each card.
- Stats: wpa-scale-up on the stats bar.
- Testimonials: wpa-fade-up with wpa-delay-200.
- CTA button: wpa-glow.
- Hero, trainers, pricing: NO animations. Strength is stillness.

SECTION ARCHITECTURE

Section 1 — HERO (Intensity)
- Purpose: Visceral motivation. The viewer should feel their heart rate rise.
- Layout: core/cover (align full, minHeight 100vh, customOverlayColor #022c22 at 75% opacity). Search media library for gym, CrossFit, weightlifting, fitness images.
- Content (centered, max-width 700px):
  - Overline: "APEX PERFORMANCE — AUSTIN, TX" — 12px, weight 700, #10b981, uppercase, letter-spacing 0.15em
  - H1: "REDEFINE YOUR LIMITS" — 72px, weight 900, uppercase, #d1fae5, letter-spacing -0.04em, line-height 1.0. No gradient. Pure impact.
  - Paragraph: "APEX is not a gym. It's a training facility for athletes who refuse to plateau. CrossFit, Olympic lifting, HIIT, and personalized strength programming — all under one roof, all coached by competitors who have been where you're going." — 18px, #6ee7b7, max-width 600px, line-height 1.7
  - Two buttons:
    - "START YOUR FREE TRIAL" — bg #10b981, text #022c22, weight 700, uppercase, padding 18px 44px, border-radius 8px, className wpa-glow
    - "VIEW PROGRAMS" — bg transparent, border 2px solid rgba(16,185,129,0.4), text #d1fae5, weight 700, uppercase, padding 18px 44px, border-radius 8px

Section 2 — PROGRAMS (What We Offer)
- Purpose: Show breadth and depth. Every visitor finds their program.
- Layout: core/group (align full, bg #022c22, padding 100px 40px) → constrained.
- Header (centered):
  - Overline: "PROGRAMS" — 12px, #10b981, uppercase
  - H2: "TRAIN WITH PURPOSE" — 48px, weight 800, uppercase, #d1fae5
  - Paragraph: "Four world-class programs. Twelve certified coaches. One standard: excellence." — 17px, #6ee7b7, centered
- 4 columns (gap 24px, className wpa-stagger-children). Each card:
  - core/group (bg rgba(16,185,129,0.06), border 1px solid rgba(16,185,129,0.12), padding 32px, border-radius 12px, className wpa-fade-up)
  - H3: program name — 22px, weight 700, #d1fae5, uppercase
  - Body: 16px, #6ee7b7, 3 sentences
  - Programs:
    1. "CROSSFIT" — "Constantly varied functional movements at high intensity. Our CrossFit program follows competition-level programming adapted for all skill levels. Classes run 6am–8pm daily with a 12:1 athlete-to-coach ratio. Whether you're prepping for the Open or touching a barbell for the first time — the programming scales."
    2. "OLYMPIC LIFTING" — "Snatch. Clean & jerk. The two lifts that demand more technical precision than any other movement in sport. Our Oly program is coached by two national-level competitors and runs three dedicated sessions per week with video analysis and individualized cue correction."
    3. "HIIT CONDITIONING" — "30-minute high-intensity interval sessions designed to torch body fat while preserving muscle. Heart rate-monitored, coach-led, and brutal in the best way. Three class formats rotate weekly: engine builder, grip ripper, and total body blitz."
    4. "PERSONAL TRAINING" — "One-on-one programming for athletes with specific goals — competition prep, injury rehab, sport-specific training, or body composition. Your coach designs a 12-week periodized program, tracks your metrics weekly, and adjusts in real time."

Section 3 — COACHES (Authority)
- Purpose: Credibility through credentials. The coaching staff IS the product.
- Layout: core/group (align full, bg #03301e, padding 100px 40px) → constrained.
- Header (centered):
  - Overline: "COACHING STAFF" — 12px, #10b981, uppercase
  - H2: "LED BY ATHLETES" — 48px, weight 800, uppercase, #d1fae5
- 3 columns (gap 28px). Each card:
  - core/group (bg rgba(16,185,129,0.06), border 1px solid rgba(16,185,129,0.12), padding 32px, border-radius 12px)
  - Search media library for fitness/trainer/coach images. Border-radius 8px.
  - Name: 22px, weight 700, #d1fae5, uppercase
  - Credential: 14px, weight 600, #10b981, uppercase, letter-spacing 0.08em
  - Bio: 16px, #6ee7b7, 2 sentences
  - Coaches:
    1. "COACH MARCUS REED" / "CF-L3 | 2x CROSSFIT GAMES ATHLETE" / "Marcus competed at the CrossFit Games in 2019 and 2021. He brings competition-level intensity to every class while meeting athletes exactly where they are. His specialty: building engine capacity in intermediate athletes who want to compete."
    2. "COACH DIANA MORALES" / "USAW LEVEL 2 | NATIONAL QUALIFIER" / "Diana has coached Olympic lifting for 9 years and produced 14 national-level qualifiers from APEX alone. Her cue precision and eye for technical breakdown are unmatched in Austin. She coaches with patience and expects commitment."
    3. "COACH JAMES OKONKWO" / "CSCS | NASM-CPT | SPORTS SCIENCE MS" / "James holds a Master's in Sports Science from UT Austin and has worked with D1 athletes, military operators, and everyday people who want to feel exceptional. His personal training programs are data-driven and results-obsessed."

Section 4 — PRICING (Conversion)
- Purpose: Remove price objection. Three clear tiers.
- Layout: core/group (align full, bg #022c22, padding 100px 40px) → constrained.
- Header (centered):
  - H2: "MEMBERSHIP" — 48px, weight 800, uppercase, #d1fae5
  - Paragraph: "No contracts. No signup fees. Cancel anytime. Start with a free trial." — 17px, #6ee7b7
- 3 columns (gap 24px). Each pricing card:
  - core/group (bg #03301e, border 1px solid rgba(16,185,129,0.12), padding 40px, border-radius 12px)
  - Tier name: 14px, weight 700, #10b981, uppercase, letter-spacing 0.1em
  - Price: 48px, weight 900, #d1fae5
  - Period: 16px, #6ee7b7 ("per session" / "per month" / "per month")
  - Feature list: 4–5 features, 15px, #6ee7b7
  - CTA button at bottom
  - Tiers:
    1. "DROP-IN" / "$25" / "per session" / Includes: any single class, all equipment, locker room access, post-workout shake bar. Button: "Book a Session"
    2. "UNLIMITED" (highlighted — border 2px solid #10b981, badge "Most Popular" in emerald pill) / "$149" / "per month" / Includes: unlimited classes, all programs, open gym access, quarterly InBody scan, member events. Button: "Start Free Trial"
    3. "ELITE" / "$249" / "per month" / Includes: everything in Unlimited, plus 4 personal training sessions/month, custom nutrition plan, priority booking, competition prep programming. Button: "Start Free Trial"

Section 5 — STATS (Social Proof)
- Purpose: Quantify community and track record.
- Layout: core/group (align full, bg #10b981, padding 80px 40px, className wpa-scale-up) → constrained → core/columns (4 columns, centered).
- Each stat:
  - Number: 48px, weight 900, #022c22 (dark text on emerald bg)
  - Label: 13px, weight 600, rgba(2,44,34,0.7), uppercase, letter-spacing 0.08em
- Stats:
  - "500+" / "Active Members"
  - "12" / "Certified Coaches"
  - "4.9/5" / "Google Rating"
  - "6" / "Years Running"

Section 6 — TESTIMONIALS (Member Voice)
- Purpose: Real transformation stories from real members.
- Layout: core/group (align full, bg #03301e, padding 100px 40px, className "wpa-fade-up wpa-delay-200") → constrained.
- Header: H2: "MEMBER STORIES" — 44px, weight 800, uppercase, #d1fae5, centered
- 3 columns (gap 24px). Each:
  - core/group (bg rgba(16,185,129,0.06), border 1px solid rgba(16,185,129,0.12), padding 32px, border-radius 12px, border-left 3px solid #10b981)
  - Quote: 16px, #d1fae5, italic, line-height 1.7
  - Name: 15px, weight 600, #d1fae5
  - Detail: 14px, #6ee7b7
  - Testimonials:
    1. "I walked into APEX 18 months ago unable to do a single pull-up. Last month I competed in my first CrossFit competition and finished in the top 20. Coach Marcus met me where I was and built a program that pushed me without breaking me. This place changed my body and my confidence." — Sarah T., Member since 2023
    2. "I've trained at six gyms in Austin. APEX is the only one where the coaches actually watch your form, correct your lifts, and program with intention. The community is intense but welcoming. My deadlift went from 315 to 405 in my first year." — David Reyes, Member since 2022
    3. "As a former D1 swimmer, I needed a gym that could match the intensity I was used to. APEX delivered. The Oly lifting program under Coach Diana is world-class — she fixed technical issues I'd been carrying for years. I PR'd my clean & jerk by 15kg in six months." — Mia Okafor, Member since 2024

Section 7 — CTA (Free Trial Conversion)
- Purpose: Lowest-friction entry point. Remove all barriers.
- Layout: core/group (align full, gradient "linear-gradient(135deg, #022c22 0%, #064e3b 50%, #10b981 100%)", padding 120px 40px, centered).
- Content:
  - H2: "YOUR FIRST SESSION IS FREE" — 52px, weight 900, uppercase, #ffffff
  - Paragraph: "No commitment. No credit card. Walk in, train with us, see if APEX is where you belong. Book your free trial session online or just show up — we open at 5:30am, seven days a week." — 18px, rgba(255,255,255,0.9), max-width 560px
  - Button: "BOOK YOUR FREE TRIAL" — bg #ffffff, text #022c22, weight 700, uppercase, padding 20px 52px, border-radius 8px, className wpa-glow
  - Below button: "3847 S Congress Ave, Austin TX | (512) 555-0187" — 14px, rgba(255,255,255,0.6)

CONTENT RULES
- Write like a coach, not a marketer. Direct, specific, no fluff.
- Every claim has a number: weights, times, percentages, credentials.
- Uppercase headings. Short sentences. Active voice. "We train." not "Training is provided."
- Dark page mode (wpa-page-dark). Deep green canvas, zero gaps.
```

---

### Prompt 9: Multi-Page Site — "Pinnacle Consulting" (5-Page Corporate Build)

```
Act like a corporate brand agency that builds websites for McKinsey, Bain, and Deloitte: a senior designer who understands that consulting websites sell trust, not flash; a B2B copywriter who writes for Harvard Business Review and McKinsey Quarterly; and a UX architect who knows multi-page information architecture.

Task: Generate a complete 5-page website for "Pinnacle Consulting" — a management consulting firm with offices in New York, London, and Singapore. This is a full multi-page build — create all 5 pages, set up the navigation menu linking them, and set the homepage as the static front page.

DESIGN SYSTEM (Clean White theme with navy accent)

Color palette:
- Background: #ffffff (clean white)
- Background alternate: #f8fafc (blue-tinted off-white for alternating sections)
- Surface / cards: #ffffff with box-shadow
- Dark sections (hero, CTA): #1e3a5f (navy)
- Primary: #1e3a5f (deep navy — authority, trust)
- Primary hover: #2d5a8e (lighter navy)
- Accent: #f59e0b (warm amber — used for stats, highlights, badges)
- Text primary: #111827 (near-black)
- Text secondary: #6b7280 (gray)
- Text on dark: #f8fafc (near-white)
- Border: rgba(0,0,0,0.06)
- Card shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04)

Typography:
- Hero H1: 56–64px, weight 700, letter-spacing -0.02em, line-height 1.1
- Section H2: 38–44px, weight 700, letter-spacing -0.01em, line-height 1.2
- Card H3: 20–22px, weight 600, line-height 1.3
- Body: 17px, weight 400, line-height 1.7, color #6b7280
- Overline: 13px, weight 600, uppercase, letter-spacing 0.1em, color #1e3a5f
- Button: 15px, weight 600
- Stat numbers: 44px, weight 700, #1e3a5f (on light bg) or #f59e0b (on dark bg)

Spacing:
- Section padding: 100px vertical. Horizontal: 48px. Inner: 1180px.
- Card padding: 32px. Gap: 28px. Card border-radius: 12px.
- Button: padding 16px 40px, border-radius 8px.
- Between sections: 0px.

Animation: Minimal. wpa-fade-up on service cards, wpa-stagger-children on team grid. Everything else static. Light page mode (wpa-page-light).

PAGE 1 — HOME (5 sections)

Section 1 — HERO
- Layout: core/cover (align full, minHeight 85vh, customOverlayColor #1e3a5f at 80% opacity). Search media library for business/office/skyline images.
- Content (centered, max-width 700px):
  - Overline: "MANAGEMENT CONSULTING" — 13px, rgba(248,250,252,0.6), uppercase
  - H1: "Strategy That Moves Markets" — 60px, weight 700, #f8fafc
  - Paragraph: "Pinnacle Consulting partners with Fortune 500 leaders and high-growth companies to solve their most complex strategic, operational, and organizational challenges. We don't deliver reports — we deliver results." — 18px, rgba(248,250,252,0.8), max-width 580px
  - Button: "Explore Our Work" — bg #f59e0b, text #1e3a5f, weight 600, padding 16px 40px, border-radius 8px

Section 2 — SERVICES OVERVIEW (3 cards)
- Layout: core/group (align full, bg #ffffff, padding 100px 48px) → constrained.
- Header: Overline "What We Do" + H2 "Three Practices. One Mission." — centered
- 3 columns. Each card: bg #f8fafc, border-top 3px solid #1e3a5f, padding 32px, border-radius 12px. H3 + 2-sentence description.
  1. "Strategy & Growth" — "Market entry, competitive positioning, and organic growth strategy for companies navigating inflection points. We've guided 40+ clients through transformational pivots that generated $2B+ in cumulative value creation."
  2. "Operations Excellence" — "Supply chain optimization, cost reduction, and operational restructuring. Our operations practice has delivered an average 23% cost reduction across 85 engagements since 2012."
  3. "Digital Transformation" — "Technology strategy, data analytics, and AI implementation for enterprises modernizing their core operations. We bridge the gap between boardroom ambition and technical execution."

Section 3 — STATS (4 metrics on navy bg)
- Layout: core/group (align full, bg #1e3a5f, padding 80px 48px) → 4 columns.
- Stats: "180+" / "Clients Served" | "14" / "Industry Verticals" | "22" / "Years of Excellence" | "$8.4B" / "Value Delivered"
- Numbers in #f59e0b, labels in rgba(248,250,252,0.7).

Section 4 — TESTIMONIALS (2 cards)
- 2 columns on white bg. Each: border-left 3px solid #1e3a5f, padding 32px.
  1. "Pinnacle's strategy team helped us navigate a $400M acquisition that doubled our market share in APAC. Their due diligence was surgical and their integration playbook saved us six months of transition chaos." — CFO, Global Industrial Conglomerate
  2. "We engaged Pinnacle to redesign our supply chain after COVID exposed critical vulnerabilities. They delivered a resilient, multi-sourced model that reduced our cost-to-serve by 19% while cutting lead times in half." — COO, Fortune 200 Retailer

Section 5 — CTA
- Navy gradient bg. H2: "Let's Solve What Matters" + paragraph + "Schedule a Conversation" button (amber).

PAGE 2 — ABOUT (5 sections)

Section 1 — Hero: "Our Story" — brief dark section with H1 and 1-paragraph founding narrative.
Section 2 — Company Story: Media-text split. Left: 3 paragraphs about founding in 2003, growth from 5 to 240 consultants, philosophy of "results over reports." Right: office/team image.
Section 3 — Mission & Values: H2 "What We Believe" + 3 columns:
  1. "Rigor Over Rhetoric" — "We don't guess. Every recommendation is grounded in data, stress-tested against scenarios, and pressure-tested with leadership before we present it."
  2. "Partnership Over Projects" — "We don't hand off a deck and disappear. Our teams embed with yours, transfer knowledge, and stay until the results are real."
  3. "Impact Over Activity" — "We measure success by outcomes — revenue generated, costs reduced, capabilities built — not by hours billed or slides produced."
Section 4 — Team: H2 "Leadership Team" + 4 columns (name, title, 1-sentence bio, search for professional headshots):
  1. "Katherine Wu" / "Managing Partner" / "Former BCG principal. 20 years in strategy consulting across healthcare, technology, and industrial sectors."
  2. "James Adeyemi" / "Partner — Operations" / "McKinsey alum. Specializes in supply chain transformation and post-merger integration."
  3. "Elena Voronova" / "Partner — Digital" / "Built the digital practice from scratch in 2018. Former CTO of a Series C fintech."
  4. "Raj Mehta" / "Partner — Singapore" / "Leads the APAC practice. 15 years of cross-border M&A advisory experience."
Section 5 — Timeline: H2 "Our Journey" + vertical timeline: 2003 (Founded in NYC), 2008 (London office), 2014 (100th client milestone), 2018 (Digital practice launched), 2021 (Singapore office), 2024 (240 consultants across 3 offices).

PAGE 3 — SERVICES (4 sections)

Section 1 — Hero: "Our Services" with brief intro paragraph.
Section 2 — 4 detailed service cards (full-width, stacked). Each: H3, 2 paragraphs, "Key capabilities" bullet list, and a result metric.
  1. "Strategy & Growth" — Market sizing, competitive analysis, growth roadmaps, pricing strategy, M&A target identification. "Average client revenue increase: 34% over 24 months."
  2. "Operations Excellence" — Supply chain design, procurement optimization, lean manufacturing, logistics network redesign. "Average cost reduction: 23% across 85 engagements."
  3. "Digital Transformation" — Technology architecture, data strategy, AI/ML implementation, change management, digital operating model. "Average time-to-value: 40% faster than industry benchmark."
  4. "M&A Advisory" — Due diligence, valuation, integration planning, synergy capture, Day 1 readiness. "$12B+ in completed deal value advised."
Section 3 — Industries: 4-column grid listing industries served: Healthcare, Technology, Financial Services, Industrial & Manufacturing, Consumer Goods, Energy & Utilities, Telecommunications, Private Equity.
Section 4 — CTA: "Ready to talk strategy?" with contact button.

PAGE 4 — CASE STUDIES (3 sections)

Section 1 — Hero: "Selected Work" with intro.
Section 2 — 3 case study cards (2-column layout, image left, content right). Each: client industry, challenge, approach, result metric.
  1. "Healthcare | Revenue Growth" — "A $4B hospital network facing declining patient volumes and margin compression." → "Pinnacle redesigned their service line strategy, launched three new specialty centers, and built a digital patient acquisition engine." → "Result: $340M in incremental annual revenue within 18 months."
  2. "Technology | Post-Merger Integration" — "Two enterprise SaaS companies merging after a $1.2B acquisition with overlapping products and cultures." → "We led a 200-day integration program covering org design, product rationalization, and go-to-market unification." → "Result: 95% employee retention, $80M in realized synergies in Year 1."
  3. "Industrial | Supply Chain Resilience" — "A global manufacturer with single-source dependencies exposed by the 2021 supply crisis." → "Pinnacle redesigned their supplier network across 4 continents, implemented dual-sourcing for critical components, and built a real-time risk monitoring dashboard." → "Result: 19% cost reduction, zero production stoppages in 24 months."
Section 3 — CTA: "Every challenge has a strategy. Let's find yours." with button.

PAGE 5 — CONTACT (3 sections)

Section 1 — Hero: "Get In Touch" with brief intro.
Section 2 — Two-column layout. Left: contact details (email, phone, general inquiry form placeholder). Right: 3 office locations:
  1. "New York (HQ)" — "One World Trade Center, Suite 4200, New York, NY 10007" / "+1 (212) 555-0340"
  2. "London" — "22 Bishopsgate, Level 38, London EC2N 4BQ" / "+44 20 7946 0958"
  3. "Singapore" — "Marina Bay Financial Centre, Tower 2, Level 30" / "+65 6303 0455"
Section 3 — Map placeholder section with dark bg and text: "Visit us at any of our three global offices."

SITE CONFIGURATION
- Create navigation menu "Main Menu" with: Home, About, Services, Case Studies, Contact.
- Set Home as the static front page.
- Apply Clean White theme colors throughout all pages.
- Consistent header and footer styling across all pages.
```

---

### Prompt 10: eCommerce Product Launch — "AeroFrame" (Apple-Inspired Dark)

```
Act like an Apple product launch design team: a senior industrial design-turned-web designer who understands that product pages sell desire through restraint; a product copywriter who has written for Apple.com, Nothing, and Teenage Engineering; and a conversion optimizer who knows that premium products convert through confidence, not urgency.

Task: Build an 8-section product launch landing page for "AeroFrame" — a premium carbon fiber laptop stand. The page must feel like an Apple product page: dark, cinematic, product-obsessed. Every pixel exists to make the viewer want to own this object.

DESIGN SYSTEM

Color palette (minimal — 3 colors plus shades):
- Background: #0a0a0a (true black — the product floats against darkness)
- Surface: #141414 (slightly lifted for cards)
- Elevated surface: #1a1a1a (for FAQ, comparison)
- Primary: #3b82f6 (precise blue — used ONLY for one CTA and price highlight)
- Silver/metal: #c0c0c0 (matches the product material — used for secondary text)
- Chrome: #e5e5e5 (brighter silver for headings on dark)
- Text primary: #fafafa (near-white)
- Text secondary: #a1a1aa (silver gray)
- Text muted: #71717a (dim)
- Border: rgba(255,255,255,0.06)
- Glass card: backdrop-filter blur(20px) saturate(180%), bg rgba(255,255,255,0.04), border 1px solid rgba(255,255,255,0.08), border-radius 16px
- Price highlight: #3b82f6 for the active/early-bird price

Typography (Apple-inspired — clean, light-weight for body, heavy for headlines):
- Hero H1: 64–72px, weight 700, letter-spacing -0.04em, line-height 1.05
- Hero tagline: 22px, weight 400, letter-spacing -0.01em, #a1a1aa (lighter, Apple-style subtitle)
- Section H2: 44–48px, weight 700, letter-spacing -0.02em, line-height 1.15
- Card H3: 20–22px, weight 600, line-height 1.3
- Body: 17px, weight 400, line-height 1.7, #a1a1aa
- Spec label: 13px, weight 600, uppercase, letter-spacing 0.1em, #71717a
- Spec value: 17px, weight 500, #fafafa
- Price: 48px, weight 700
- Strikethrough price: 24px, weight 400, #71717a, text-decoration line-through
- FAQ question: 18px, weight 600, #fafafa
- FAQ answer: 16px, weight 400, #a1a1aa

Spacing:
- Section padding: 120px vertical (hero: 140px). Horizontal: 48px. Inner: 1100px.
- Card padding: 36px. Gap: 24px. Card border-radius: 16px.
- Button: padding 18px 48px, border-radius 100px (pill — Apple-style).
- Between sections: 0px.

Animation:
- Features bento: wpa-stagger-children + wpa-glass on each card
- Comparison: wpa-fade-up
- Stats: wpa-scale-up
- CTA buttons: wpa-glow (only on primary buttons)
- Hero, testimonials, FAQ, pricing: NO animations. Product confidence is still.

SECTION ARCHITECTURE

Section 1 — HERO (Product Reveal)
- Purpose: Instant desire. The product should feel like a piece of sculpture.
- Layout: core/group (align full, bg #0a0a0a, padding 140px 48px, centered) → constrained.
- Content (centered, max-width 700px):
  - Product name: "AeroFrame" — 72px, weight 700, #fafafa, letter-spacing -0.04em
  - Tagline: "Engineered for Those Who Build" — 22px, weight 400, #a1a1aa, letter-spacing -0.01em. This sits directly below the name with 12px gap — Apple-style pairing.
  - (24px gap)
  - Search media library for laptop stand / desk / product images. Center the product image, max-width 800px, no border-radius.
  - (32px gap)
  - Paragraph: "180 grams of aerospace-grade carbon fiber. A 7-degree ergonomic angle derived from 18 months of biomechanical research. Magnetic cable management that disappears when you don't need it. AeroFrame is the laptop stand for people who care about every detail of their workspace." — 18px, #a1a1aa, max-width 580px, centered, line-height 1.7
  - Button: "Pre-Order — $129" — bg #3b82f6, text white, weight 600, border-radius 100px, padding 18px 48px, className wpa-glow

Section 2 — FEATURES (Bento Grid)
- Purpose: Deep product specs presented as design objects, not bullet points.
- Layout: core/group (align full, bg #0a0a0a, padding 120px 48px) → constrained.
- Header (centered):
  - H2: "Every Detail, Considered" — 46px, weight 700, #fafafa
  - Paragraph: "Four engineering breakthroughs in one minimal form." — 17px, #a1a1aa
- Bento grid (className "wpa-bento-grid wpa-stagger-children"). 4 feature cards:
  - Card 1 (large — spans 8): "7° Ergonomic Angle" — "The angle isn't arbitrary. It's the result of a partnership with the Stanford Ergonomics Lab. 7 degrees reduces wrist extension by 34%, decreases neck strain by 28%, and aligns your eye line with the top third of the screen — exactly where research shows optimal focus lives. Your body will feel the difference in the first hour. After a week, you'll wonder how you worked without it." className wpa-glass, padding 40px.
  - Card 2 (small — spans 4): "Aerospace Carbon Fiber" — "The same material used in Formula 1 monocoques and satellite structures. Tensile strength of 3,500 MPa. Weighs 180g — less than your phone. Supports up to 20kg without flex. Hand-finished with a satin weave that catches light like polished stone." className wpa-glass.
  - Card 3 (spans 6): "Magnetic Cable Management" — "Two neodymium magnets embedded in the rear channel. Thread your charging cable through once. It stays. No clips, no adhesive, no ugly plastic. When you disconnect, the cable drops flush with the stand. Invisible until you need it." className wpa-glass.
  - Card 4 (spans 6): "Universal Compatibility" — "Fits every laptop from 11 to 16 inches. MacBook Air to ThinkPad X1 to Dell XPS. Non-slip silicone contact pads protect your laptop's finish. The base footprint is just 24cm x 18cm — smaller than a sheet of paper." className wpa-glass.

Section 3 — COMPARISON (Rational Decision Support)
- Purpose: Make AeroFrame the obvious choice against alternatives.
- Layout: core/group (align full, bg #141414, padding 100px 48px, className wpa-fade-up) → constrained.
- Header (centered): H2: "How AeroFrame Compares" — 44px, #fafafa
- Comparison table (3 columns):
  - Headers: "AeroFrame" (highlighted) / "Aluminum Stand" / "Wooden Stand"
  - Rows:
    1. Material: "Aerospace carbon fiber" / "6061 aluminum" / "Walnut/bamboo"
    2. Weight: "180g" / "420g" / "680g"
    3. Max Load: "20kg" / "12kg" / "8kg"
    4. Cable Management: "Magnetic (built-in)" / "None" / "Slot (exposed)"
    5. Price: "$129 (early bird)" / "$49–89" / "$35–75"
  - AeroFrame column: #fafafa text, bg rgba(59,130,246,0.06), border-left 2px solid #3b82f6
  - Others: #a1a1aa text, bg #1a1a1a

Section 4 — SOCIAL PROOF (Press + Stats)
- Purpose: Third-party validation. Press logos > testimonials for products.
- Layout: core/group (align full, bg #0a0a0a, padding 80px 48px, className wpa-scale-up) → constrained → core/columns (4 columns, centered).
- Each:
  - Number: 44px, weight 700, #fafafa
  - Label: 13px, weight 500, #71717a, uppercase, letter-spacing 0.08em
- Stats:
  - "15,000+" / "Pre-Orders"
  - "4.9/5" / "Beta Tester Rating"
  - "Wired" / "Editor's Choice"
  - "The Verge" / "Best of CES 2025"

Section 5 — TESTIMONIALS (Early Adopter Voice)
- Purpose: Relatable social proof from real-sounding users.
- Layout: core/group (align full, bg #141414, padding 100px 48px) → constrained.
- Header: H2: "What Early Adopters Say" — 42px, #fafafa, centered
- 3 columns (gap 24px). Each card:
  - core/group (bg #1a1a1a, border 1px solid rgba(255,255,255,0.06), padding 32px, border-radius 16px)
  - Quote: 16px, #e5e5e5, italic
  - Name + Role: 14px, #71717a
  - Testimonials:
    1. "I've spent embarrassing amounts of money optimizing my desk setup. The AeroFrame is the first stand that actually looks as good as the laptop sitting on it. The carbon fiber weave catches light beautifully. And at 180g, I throw it in my bag for coffee shop sessions." — Alex P., Software Engineer
    2. "My physical therapist recommended elevating my laptop. I tried three aluminum stands — all too heavy, too ugly, or too wobbly. AeroFrame is none of those things. It's rock solid at 180 grams. The 7-degree angle genuinely reduced my neck pain within a week." — Maria K., Product Designer
    3. "The magnetic cable management is the detail that made me a believer. No more cable clips. No more desk clutter. The charging cable just... stays there. Invisible. It's such a small thing but it completely changed how my desk feels." — James L., Creative Director

Section 6 — PRICING (Single Product, Two Options)
- Purpose: Clear, confident pricing. No confusion.
- Layout: core/group (align full, bg #0a0a0a, padding 120px 48px, centered) → constrained (max-width 600px).
- Content:
  - H2: "Choose Your AeroFrame" — 44px, #fafafa
  - (32px gap)
  - Early bird price: "$129" — 48px, weight 700, #3b82f6
  - Regular price (struck): "$179" — 24px, weight 400, #71717a, text-decoration line-through
  - Label: "Early Bird — Limited to First 5,000 Orders" — 14px, weight 600, #a1a1aa, uppercase
  - (24px gap)
  - Includes list: "Free worldwide shipping | 2-year warranty | 30-day money-back guarantee | Carry sleeve included" — 16px, #a1a1aa, centered
  - (32px gap)
  - Button: "Pre-Order Now — $129" — bg #3b82f6, text white, weight 600, border-radius 100px, padding 20px 56px, className wpa-glow

Section 7 — FAQ (Objection Handling)
- Purpose: Remove final purchase hesitations.
- Layout: core/group (align full, bg #141414, padding 100px 48px) → constrained (max-width 800px).
- Header: H2: "Questions" — 44px, #fafafa
- 5 FAQ items (each: question as H3 18px weight 600 #fafafa, answer as paragraph 16px #a1a1aa):
  1. "What material is AeroFrame made from?" — "Aerospace-grade T700 carbon fiber with a satin weave finish. The same material class used in Formula 1 monocoques, bicycle frames, and satellite structures. It's hand-laid and pressure-cured for maximum strength-to-weight ratio."
  2. "Will it fit my laptop?" — "AeroFrame is universally compatible with laptops from 11 to 16 inches, including MacBook Air/Pro, Dell XPS, ThinkPad X1, Surface Laptop, and HP Spectre. Maximum supported weight is 20kg."
  3. "What if I don't like it?" — "30-day money-back guarantee, no questions asked. Return it in any condition. We'll cover return shipping and issue a full refund within 5 business days."
  4. "When does the early bird pricing end?" — "Early bird pricing of $129 (vs $179 regular) is available until we reach 5,000 pre-orders or March 31, 2025 — whichever comes first. After that, the price increases to $179."
  5. "How does the magnetic cable management work?" — "Two neodymium N52 magnets are embedded in a channel along the rear edge of the stand. Thread any MagSafe, USB-C, or Lightning cable through the channel. The magnets hold it in place — firmly enough to stay put, gently enough to release with a light pull."

Section 8 — CTA (Final Push with Urgency)
- Purpose: Last chance. Urgency without desperation.
- Layout: core/group (align full, gradient "linear-gradient(135deg, #0a0a0a 0%, #172554 50%, #3b82f6 100%)", padding 120px 48px, centered).
- Content:
  - H2: "Early Bird Pricing Ends March 31" — 48px, weight 700, #ffffff
  - Paragraph: "15,000 people have already pre-ordered. The first batch ships April 15. Secure yours before the price goes to $179." — 18px, rgba(255,255,255,0.85), max-width 520px
  - Button: "Pre-Order AeroFrame — $129" — bg #ffffff, text #0a0a0a, weight 600, border-radius 100px, padding 20px 56px, className wpa-glow

CONTENT RULES
- Write like an Apple product page. Short paragraphs. Specific numbers. Material science details.
- Every feature has a measurable benefit: weight in grams, angles in degrees, percentages of improvement.
- No exclamation marks. No "amazing." Confidence is quiet.
- Dark page mode (wpa-page-dark). Zero gaps. Glass cards for features only.
```

---

### Prompt 11: Reference + Blueprint — "Quillbot Pro" (Analyzer + Blueprint Combo Demo)

```
Act like a SaaS growth design team: a senior product designer who has built marketing pages for Jasper, Copy.ai, and Grammarly; a content marketing strategist who understands that AI writing tools sell trust and output quality; and a conversion optimizer who knows SaaS landing pages live and die by the hero and pricing sections.

IMPORTANT: This prompt demonstrates two power features working together — reference site analysis and blueprint customization.

STEP 1 — ANALYZE THE REFERENCE SITE
Use analyze_reference_site on https://jasper.ai to extract:
- Their exact color palette (primary, accent, background, text colors)
- Typography hierarchy (heading sizes, body font, weight distribution)
- Section structure (what sections they use, in what order)
- Design approach (dark vs light, card styles, animation usage)
- Pattern suggestions (which patterns from our library match each of their sections)

STEP 2 — SELECT AND CUSTOMIZE THE BLUEPRINT
Use the modern-saas blueprint as the structural foundation. The modern-saas blueprint includes:
hero-aurora → logo-bar-glass → features-bento → stats-gradient → testimonials-modern → pricing-glass → faq-modern → cta-aurora

Customize the blueprint with these modifications:
- OVERRIDE the blueprint's default colors with the palette extracted from Jasper
- If Jasper uses a dark theme, keep dark. If light, switch to light. Match their energy.
- ADD a logo-bar section after the hero if the blueprint doesn't include one
- REPLACE testimonials with quotes from content marketers and agency owners (not generic SaaS users)
- Use the pattern suggestions from the analyzer to pick the best-matching pattern for each section

STEP 3 — CONTENT BRIEF FOR EACH SECTION

The product: "Quillbot Pro" — an AI writing assistant that adapts to your brand voice. Not a generic text generator — a writing partner that learns how your brand sounds and produces content that's indistinguishable from your best human writer.

Section 1 — HERO
- H1: Use the Jasper-extracted primary color for gradient text. Headline: "Write Like Your Best Writer. Every Time."
- Sub: "Quillbot Pro learns your brand voice from your existing content — then generates blog posts, emails, ad copy, and social content that sounds exactly like you. Not generic AI. Your AI."
- Two buttons: "Start Free Trial" (primary color, glow effect) + "See It Write" (ghost/outline)
- Use hero-aurora pattern with Jasper's color palette for the aurora blobs

Section 2 — LOGO BAR
- "Trusted by 1,500+ marketing teams" with marquee of company names
- Use logo-bar-glass or logo-bar-dark depending on Jasper's theme

Section 3 — FEATURES (Bento Grid)
- Use features-bento pattern. 4 features:
  1. "Brand Voice Engine" (large card) — "Upload 10 pieces of your best content. Quillbot analyzes tone, vocabulary, sentence structure, and style markers. In 60 seconds, it builds a voice model unique to your brand. Every output sounds like you wrote it — because it learned from what you wrote."
  2. "SEO Optimization" — "Real-time SERP analysis, keyword density scoring, and semantic optimization. Quillbot doesn't just write — it writes content that ranks. Average first-page ranking within 47 days for long-tail keywords."
  3. "Plagiarism Shield" — "Every piece of content is checked against a 10-billion-page index before delivery. Originality scores, source attribution, and AI-detection-safe outputs. Your content is yours — verifiably."
  4. "Team Collaboration" — "Shared brand voice models, approval workflows, content calendars, and version history. Your entire marketing team writes with one voice, managed from one dashboard."
- Glass cards with lift hover. Stagger animation.

Section 4 — STATS
- Use stats-gradient pattern with Jasper's accent color.
- Stats: "1,500+" / "Marketing Teams" | "4.2M" / "Pieces of Content Generated" | "47 Days" / "Avg Time to Page 1" | "4.8/5" / "G2 Rating"

Section 5 — TESTIMONIALS
- Use testimonials-modern pattern. 3 quotes from content marketing professionals:
  1. "We replaced three freelance writers with Quillbot Pro — not because it's cheaper, but because it's more consistent. Every blog post sounds like our brand. The voice model is genuinely uncanny." — Head of Content, B2B SaaS Company (200 employees)
  2. "Our agency manages content for 40 clients. Each client has a different voice. Quillbot Pro lets us switch between 40 brand voice models instantly. What used to take a writer 30 minutes of context-switching now takes zero." — Founder, Content Marketing Agency
  3. "The SEO module alone paid for the annual subscription in the first month. We went from publishing 4 blog posts per month to 16 — all ranking, all on-brand, all original." — Marketing Director, eCommerce Brand

Section 6 — PRICING
- Use pricing-glass pattern. 3 tiers:
  1. "Starter" / $0/mo — 1 brand voice model, 10K words/month, basic SEO, single user
  2. "Pro" / $29/mo (highlighted) — 5 brand voice models, 100K words/month, advanced SEO, plagiarism shield, 5 team members
  3. "Enterprise" / Custom — unlimited everything, SSO, API access, dedicated success manager, custom voice training

Section 7 — FAQ
- Use faq-modern pattern. 5 questions about voice model accuracy, content originality, supported formats, team features, and data privacy.

Section 8 — CTA
- Use cta-aurora pattern with Jasper's palette.
- H2: "Your Brand Voice, Amplified by AI"
- Button: "Start Free — No Credit Card" with glow effect

CONTENT RULES
- Match Jasper's design energy exactly — this should feel like a credible competitor.
- Write specific, metric-driven SaaS copy. Every feature has a number.
- Tone: Confident, professional, marketing-team-focused.
- Dark page mode (wpa-page-dark) unless Jasper's analysis shows light theme preference.
```

---

## Quick-Fire Demo Prompts (Feature Showcase)

Copy-paste these one at a time to show each capability:

### Content Management
```
Create a new blog post titled "10 Trends Reshaping AI in 2025" — write 5 detailed paragraphs with subheadings, publish as draft
```
```
Clone the homepage and rename the copy to "Homepage — A/B Variant"
```
```
Find all posts containing "old brand name" and replace with "New Brand Name" in titles and content
```
```
Show me all draft pages on this site
```

### Media
```
Search the media library for "team" images
```
```
Import this image into the media library: https://images.unsplash.com/photo-1497366216548-37526070297c
```
```
Set the featured image of the homepage to the most recent team photo in the media library
```

### Design & Theming
```
Update the site's global typography: set heading font to Inter and body font to Source Sans Pro, increase base font size to 18px
```
```
Add custom CSS: make all buttons have rounded corners (border-radius: 50px) and a subtle box shadow on hover
```
```
Switch the active theme to flavor flavor flavor flavor flavor flavor
```

### Plugins & Themes
```
What plugins are installed? Which ones have updates available?
```
```
Install and activate the flavor flavor flavor plugin
```
```
Search the theme directory for minimal portfolio themes and show me the top 3 by rating
```

### Users & Roles
```
Create a new editor account for sarah@company.com with the display name "Sarah Chen"
```
```
Show me all administrator users on this site
```
```
List all active login sessions and terminate any from unrecognized IPs
```

### SEO
```
Set the SEO meta for the homepage: title "Your Brand — Tagline Here", description "We help companies do X. Get started free today.", and Open Graph image from the media library
```
```
Check the sitemap status and ping Google for re-indexing
```
```
Run an accessibility audit on the homepage
```

### Site Administration
```
Optimize the database — clean up post revisions, expired transients, and spam comments
```
```
Show me the last 50 lines of the debug log and highlight any errors
```
```
Set up a 301 redirect from /old-page to /new-page
```
```
What does Site Health say? Any critical issues?
```
```
List all scheduled cron jobs on this site
```

### WooCommerce
```
Create a new product: "Premium Leather Wallet" — price $79, sale price $59, SKU WALLET-001, stock quantity 150, short description "Handcrafted from full-grain Italian leather"
```
```
Show me this week's sales summary — revenue, order count, and top 3 products
```
```
Create a 20% off coupon code "LAUNCH20" that expires March 31 and is limited to 100 uses
```
```
What products have less than 10 items in stock?
```

### AI Workflows
```
Remember that our brand colors are navy (#1e3a5f) and gold (#d4a853), our tone is professional but warm, and we're a B2B consulting firm
```
```
Set up an A/B test on the homepage hero: Variant A has the headline "Transform Your Business" and Variant B has "Growth Starts Here"
```
```
Undo the last action
```

### Navigation & Structure
```
Create a navigation menu called "Main Menu" with links to Home, About, Services, Blog, and Contact
```
```
Update the header template part to include a centered logo and horizontal navigation
```
```
Set the permalink structure to /blog/%postname%/
```

### Multi-Page / Full Site
```
Generate a complete 4-page website for a yoga studio called "Stillwater Yoga" — Home, About, Classes, Contact. Use a calming sage green and cream palette. Set up the navigation menu linking all pages.
```

---

## Video Walkthrough Talking Points

Quick soundbites for your stakeholder presentation:

1. **"No page builders needed"** — JARVIS builds directly with native Gutenberg blocks. No Elementor, no Divi, no code. Every page is pure WordPress.

2. **"95 patterns, 17 blueprints"** — We've curated a professional design library. The AI doesn't guess layouts — it picks from battle-tested, responsive section designs.

3. **"One command, full page"** — With `build_from_blueprint`, JARVIS can build a complete multi-section landing page in a single tool call. What used to take hours takes seconds.

4. **"Reference site intelligence"** — Paste any URL. JARVIS extracts the color palette, font hierarchy, and section structure, then rebuilds it with your content using the closest patterns from our library.

5. **"76 actions, zero context switching"** — From SEO to WooCommerce to user management to database optimization — everything stays in the chat. No navigating between 15 different admin screens.

6. **"It remembers you"** — JARVIS saves your brand colors, preferences, and business context across sessions. The more you use it, the better it gets.

7. **"Plan before execute"** — For anything destructive (deleting posts, changing themes, bulk operations), JARVIS presents a numbered plan and waits for your "go ahead." Safe by design.

8. **"WooCommerce native"** — Full store management from chat. Products, orders, coupons, shipping, inventory, analytics. If WooCommerce is active, the tools appear automatically.

9. **"Works everywhere"** — Editor sidebar for page building. Floating drawer on every admin screen for everything else. Voice input for hands-free operation.

10. **"Open AI backbone"** — Bring your own API key via OpenRouter. Use Claude, GPT-4, Gemini, Mistral — whatever model you prefer. No vendor lock-in.
