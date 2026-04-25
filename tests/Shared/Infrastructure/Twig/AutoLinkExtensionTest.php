<?php
namespace App\Tests\Shared\Infrastructure\Twig;

use App\Shared\Infrastructure\Twig\AutoLinkExtension;
use PHPUnit\Framework\TestCase;

final class AutoLinkExtensionTest extends TestCase
{
    private AutoLinkExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new AutoLinkExtension();
    }

    public function test_plain_text_is_unchanged(): void
    {
        self::assertSame('Bonjour le club', $this->ext->autolink('Bonjour le club'));
    }

    public function test_http_url_is_wrapped(): void
    {
        $result = $this->ext->autolink('Voir http://example.com ici');
        self::assertStringContainsString('<a href="http://example.com"', $result);
        self::assertStringContainsString('target="_blank"', $result);
        self::assertStringContainsString('rel="noopener noreferrer"', $result);
    }

    public function test_https_url_is_wrapped(): void
    {
        $result = $this->ext->autolink('Lien : https://example.com/path?q=1&r=2');
        self::assertStringContainsString('href="https://example.com/path?q=1&r=2"', $result);
    }

    public function test_multiple_urls_in_text(): void
    {
        $result = $this->ext->autolink('https://a.com et https://b.com');
        self::assertStringContainsString('href="https://a.com"', $result);
        self::assertStringContainsString('href="https://b.com"', $result);
    }

    public function test_text_around_link_is_preserved(): void
    {
        $result = $this->ext->autolink('Avant https://example.com après');
        self::assertStringContainsString('Avant ', $result);
        self::assertStringContainsString(' après', $result);
    }
}
