# Dreamtigers Front Page Redesign — Modern Gallery

## Overview
Replace the current single-book carousel with a modern gallery-style front page that showcases the entire catalog professionally.

## Design
- **Hero**: Centered logo, "DREAMTIGERS" title (serif), tagline in small caps
- **Featured Section**: Newest book with large cover, title, description, "Read Now" button
- **Book Grid**: 10 books in responsive grid (5/3/2 cols), hover effects, titles below covers
- **Footer**: Minimal with Facebook link and credits

## Tech Stack
- PHP + SQLite + vanilla JS/CSS (no frameworks, no Tailwind)
- CSS Grid for book layout
- Existing SEO meta tags and favicon preserved

## Files to Modify
- `index.php` — Replace carousel markup with hero, featured section, grid, footer
- `style.css` — Add gallery grid styles, hover effects, featured section, updated footer

## Data Queries
- Featured: newest book (first result of `ORDER BY created_at DESC`)
- Grid: next 10 books (skip 1, limit 10)

## Implementation Steps
1. Rewrite `index.php` to query featured + grid books, render new layout
2. Replace carousel CSS with gallery grid styles
3. Add hover effects for book covers
4. Keep all existing SEO/FB meta tags, favicon, script for mobile nav if needed
