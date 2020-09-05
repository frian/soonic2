<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /**
     * @ORM\ManyToMany(targetEntity=Album::class, inversedBy="artists")
     */
    private $albums;

    public function __toString() {
        return $this->name;
    }
    
    public function __construct()
    {
        $this->albums = new ArrayCollection();
    }

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

    /**
     * @return Collection|Album[]
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): self
    {
        if (!$this->albums->contains($album)) {
            $this->albums[] = $album;
        }

        return $this;
    }

    public function removeAlbum(Album $album): self
    {
        if ($this->albums->contains($album)) {
            $this->albums->removeElement($album);
        }

        return $this;
    }
}
