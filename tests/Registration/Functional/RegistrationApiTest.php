<?php
namespace App\Tests\Registration\Functional;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $tournamentId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');

        $id = Uuid::generate();
        $this->tournamentId = (string) $id;

        $t = Tournament::create(
            $id, 'Test Tournament',
            new \DateTimeImmutable('+1 day'),
            new \DateTimeImmutable('+2 days'),
            TournamentType::OPEN, 10, null
        );
        $t->publish();
        $em->persist($t);
        $em->flush();
    }

    public function test_register_success(): void
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'tournamentId' => $this->tournamentId,
            'lastName'     => 'Dupont',
            'firstName'    => 'Jean',
            'phone'        => '0612345678',
            'email'        => 'jean.dupont@example.com',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['ok']);
        $this->assertSame('PENDING', $data['status']);
    }

    public function test_register_validation_fails(): void
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'tournamentId' => 'not-a-uuid',
            'lastName'     => '',
            'firstName'    => 'Jean',
            'phone'        => '0612345678',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['ok']);
        $this->assertArrayHasKey('errors', $data);
    }
}
