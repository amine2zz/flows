# THE216 — Streetwear Tunisie

E-commerce streetwear brand for Tunisia. Black · Marron · White.

## Stack
- PHP + MySQL (shared hosting, `flowstn_db`)
- Vanilla JS + Chart.js
- No frameworks — pure HTML/CSS/JS

## Files
- `index.php` — Shop homepage (hero, products, stats, about)
- `order.php` — Order API (cart → DB)
- `stats.php` — Sales stats API (Chart.js data)
- `config.php` — DB config, schema init, product seed
- `assets/logo.png` — Brand logo
- `legacy/` — Archived Flows electricity voting app

## Features
- Animated hero with floating logo
- Product grid with category filters
- Size picker + cart drawer
- Checkout form → order saved to DB
- Order confirmation screen with ref number
- Live sales stats with Chart.js (doughnut + bar)
- Scroll reveal animations
- Mobile responsive

## Deploy
Upload all files to web root. Tables + seed products auto-created on first visit.

## Palette
| Token | Value |
|-------|-------|
| Black | `#0a0a0a` |
| Marron | `#3D2314` |
| Cream | `#F5F0EB` |

## Legacy
The old Flows electricity voting app is preserved in `legacy/` and in git history.
