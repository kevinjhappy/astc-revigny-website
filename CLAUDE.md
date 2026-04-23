# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Website for ASTC Revigny (Association Sportive et de Tennis Club de Revigny), a local tennis club.

Two distinct areas:
- **Public one-page site** — polished UX, club news/updates, photo gallery, general club information
- **Back office** — tournament registration management (create tournaments, handle member sign-ups)

## Developer Profile

The developer has 15 years of PHP experience. Tailor suggestions and implementations toward PHP-idiomatic patterns. No need to over-explain PHP fundamentals.

## Tech Stack

Not yet decided. When chosen, update this file with build/dev/test commands and architecture notes. Candidate directions given the PHP background: Laravel + Blade/Livewire, Symfony, or a lightweight stack (Slim + vanilla JS/Alpine.js). For the front-end, prioritize clean and attractive UX on the public page.

## Key Requirements

- Public page is a single scrollable page (one-page pattern)
- Back office requires authentication — only admins manage tournaments and registrations
- Content to display: club news, photos, general club info
- Core back-office feature: tournament inscription management
