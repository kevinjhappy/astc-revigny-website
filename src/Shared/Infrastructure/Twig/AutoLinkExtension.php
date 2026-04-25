<?php
namespace App\Shared\Infrastructure\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class AutoLinkExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('autolink', $this->autolink(...))];
    }

    public function autolink(string $text): string
    {
        return preg_replace(
            '#https?://[^\s<>"\']+#i',
            '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>',
            $text
        );
    }
}
