<?php

namespace FeedBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'feed')]
#[ORM\UniqueConstraint(columns: ['reader_id'])]
#[ORM\Entity]
class Feed
{
    #[ORM\Column(name: 'id', type: 'bigint', unique:true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private int $id;

    #[ORM\Column(name: 'reader_id', type: 'bigint', nullable: false)]
    private int $readerId;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $tweets;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'create')]
    private DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'update')]
    private DateTime $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getReaderId(): int
    {
        return $this->readerId;
    }

    public function setReaderId(int $readerId): void
    {
        $this->readerId = $readerId;
    }

    public function getTweets(): ?array
    {
        return $this->tweets;
    }

    public function setTweets(?array $tweets): void
    {
        $this->tweets = $tweets;
    }

    public function getCreatedAt(): DateTime {
        return $this->createdAt;
    }

    public function setCreatedAt(): void {
        $this->createdAt = new DateTime();
    }

    public function getUpdatedAt(): DateTime {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): void {
        $this->updatedAt = new DateTime();
    }
}
