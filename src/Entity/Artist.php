<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ArtistRepository::class)
 */
class Artist
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $artistSlug;

    /**
     * @ORM\Column(type="integer")
     */
    private $albumCount;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $coveArtPath;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getArtistSlug(): ?string
    {
        return $this->artistSlug;
    }

    public function setArtistSlug(string $artistSlug): self
    {
        $this->artistSlug = $artistSlug;

        return $this;
    }

    public function getAlbumCount(): ?int
    {
        return $this->albumCount;
    }

    public function setAlbumCount(int $albumCount): self
    {
        $this->albumCount = $albumCount;

        return $this;
    }

    public function getCoveArtPath(): ?string
    {
        return $this->coveArtPath;
    }

    public function setCoveArtPath(?string $coveArtPath): self
    {
        $this->coveArtPath = $coveArtPath;

        return $this;
    }
}
