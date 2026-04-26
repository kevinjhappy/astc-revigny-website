<?php
namespace App\Shared\Infrastructure\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class AutoLinkExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('autolink', $this->autolink(...), ['is_safe' => ['html']])];
    }

    public function autolink(string $text): string
    {
        return preg_replace_callback(
            '#<a[^>]*>.*?</a>|https?://[^\s<>"\']+#is',
            static function (array $matches): string {
                if (str_starts_with($matches[0], '<a')) {
                    return $matches[0];
                }

                return '<a href="' . $matches[0] . '" target="_blank" rel="noopener noreferrer">' . $matches[0] . '</a>';
            },
            $text
        );
    }
}
