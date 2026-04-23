<?php
namespace App\Registration\Domain;
enum RegistrationStatus: string {
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case WAITING_LIST = 'WAITING_LIST';
}
