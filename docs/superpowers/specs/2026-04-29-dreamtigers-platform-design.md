# Dreamtigers Publishing Platform — Design Spec

## Overview

Dreamtigers is a free publishing platform for digital books published by Yannis Adamis, managed by Marios Arseniou. A lightweight PHP + SQLite site with a refined, minimal front page featuring a single-book-per-slide carousel.

## Architecture

### File Structure
```
dreamtigers/
├── index.php          # Front page: logo + single-book carousel
├── book.php           # Individual book page with PDF viewer
├── admin.php          # Password-protected admin panel
├── api.php            # AJAX endpoints for admin actions
├── style.css          # Minimal & refined CSS
├── app.db             # SQLite database
├── init_db.php        # One-time: scans folders → populates DB, resizes covers
├── covers/            # Resized covers (generated)
│   ├── vertical/      # 419×595
│   └── horizontal/    # 595×419
├── uploads/           # Admin-uploaded files
│   ├── pdfs/
│   └── covers/
├── free_ebooks/       # Original PDFs
└── covers_original/   # Original cover images (from covers/)
```

### SQLite Schema
```sql
CREATE TABLE books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    pdf_filename TEXT NOT NULL,
    cover_filename TEXT NOT NULL,
    cover_orientation TEXT DEFAULT 'vertical',
    slug TEXT UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

## Components

### Front Page (index.php)
- **Hero:** Centered `logo.png`, "Dreamtigers" title, subtle tagline
- **Carousel:** One cover at a time, centered, left/right arrows, bullet indicators
- **Ordering:** Newest book first (by `created_at`)
- **Interaction:** Click cover or "View Book" button → `book.php?slug=...`
- **CSS transition:** Smooth slide (CSS `transform` or `opacity`)

### Book Page (book.php)
- Displays book title, cover image
- Embedded PDF viewer (browser-native `<iframe>` or `<object>`)
- Back link to front page

### Admin Panel (admin.php)
- Basic HTTP auth (username/password)
- Form: Title + PDF upload + Cover upload
- Auto-detect cover orientation (width/height ratio) and resize to 419×595 or 595×419
- List of existing books with edit/delete actions
- AJAX calls via `api.php`

### Init Script (init_db.php)
- Run once to populate database
- Scans `free_ebooks/` for PDFs and original `covers/` for images
- Matches PDF to cover by checking if the cover filename contains the PDF's base name (e.g., `a_edo.pdf` → `a_edo_cover.jpg`)
- If multiple covers match a PDF, the first match is used
- Auto-resizes covers to target dimensions
- Generates URL slugs from titles or filenames
- Creates `app.db` and inserts all records

## CSS Style

**Palette:**
- Background: `#ffffff`
- Text: `#1a1a1a`
- Secondary text: `#666666`
- Accent: `#2c2c2c`
- Subtle borders: `#e0e0e0`

**Typography:**
- Headings: `'Georgia', 'Times New Roman', serif`
- Body: system sans-serif stack

**Spacing:**
- Container max-width: 1400px
- Generous whitespace, breathing room
- Consistent 24px gap rhythm

**Carousel bullets:**
- Small dots, active dot darker/slightly larger
- Centered below carousel

**Responsive:**
- Carousel cover scales to fit viewport width on mobile
- Touch swipe support for carousel navigation
- Arrows visible on hover, always visible on touch devices

**Hover effects:**
- Cover: subtle scale up (`transform: scale(1.02)`) + soft shadow

## Error Handling

- Missing cover → fallback placeholder
- PDF not found → "Book not available" message
- Admin without auth → 403
- Duplicate slug on upload → auto-append number

## Deployment

- Requires PHP 7.4+ with SQLite3 and GD extensions
- Any PHP-compatible hosting (shared hosting, VPS)
- No build step, no Node.js
