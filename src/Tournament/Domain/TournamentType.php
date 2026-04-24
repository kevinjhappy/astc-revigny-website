<?php
namespace App\Tournament\Domain;
enum TournamentType: string { case OPEN = 'OPEN'; case MEMBERS_ONLY = 'MEMBERS_ONLY'; case TEN_UP = 'TEN_UP'; }
