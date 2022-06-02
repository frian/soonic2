<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AlbumRepository::class)
 */
class Album
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
    private $albumSlug;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $songCount;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $duration;

    /**
     * @ORM\Column(type="integer")
     */
    private $year;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $genre;

    /**
     * @ORM\Column(type="string", length=1024)
     */
    private $path;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $coverArtPath;

    /**
     * @ORM\ManyToMany(targetEntity=Artist::class, mappedBy="albums")
     */
    private $artists;

    /**
     * @ORM\OneToMany(targetEntity=Song::class, mappedBy="album")
     */
    private $songs;

    public function __toString()
    {
        return $this->name;
    }

    public function __construct()
    {
        $this->artists = new ArrayCollection();
        $this->songs = new ArrayCollection();
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

    public function getAlbumSlug(): ?string
    {
        return $this->albumSlug;
    }

    public function setAlbumSlug(string $albumSlug): self
    {
        $this->albumSlug = $albumSlug;

        return $this;
    }

    public function getSongCount(): ?int
    {
        return $this->songCount;
    }

    public function setSongCount(?int $songCount): self
    {
        $this->songCount = $songCount;

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): self
    {
        $this->genre = $genre;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getCoverArtPath(): ?string
    {
        return $this->coverArtPath;
    }

    public function setCoverArtPath(?string $coverArtPath): self
    {
        $this->coverArtPath = $coverArtPath;

        return $this;
    }

    /**
     * @return Collection|Artist[]
     */
    public function getArtists(): Collection
    {
        return $this->artists;
    }

    public function addArtist(Artist $artist): self
    {
        if (!$this->artists->contains($artist)) {
            $this->artists[] = $artist;
            $artist->addAlbum($this);
        }

        return $this;
    }

    public function removeArtist(Artist $artist): self
    {
        if ($this->artists->contains($artist)) {
            $this->artists->removeElement($artist);
            $artist->removeAlbum($this);
        }

        return $this;
    }

    /**
     * @return Collection|Song[]
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): self
    {
        if (!$this->songs->contains($song)) {
            $this->songs[] = $song;
            $song->setAlbum($this);
        }

        return $this;
    }

    public function removeSong(Song $song): self
    {
        if ($this->songs->contains($song)) {
            $this->songs->removeElement($song);
            // set the owning side to null (unless already changed)
            if ($song->getAlbum() === $this) {
                $song->setAlbum(null);
            }
        }

        return $this;
    }
}
