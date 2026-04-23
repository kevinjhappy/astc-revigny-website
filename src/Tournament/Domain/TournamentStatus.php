<?php
namespace App\Tournament\Domain;
enum TournamentStatus: string { case DRAFT = 'DRAFT'; case PUBLISHED = 'PUBLISHED'; case CLOSED = 'CLOSED'; }
