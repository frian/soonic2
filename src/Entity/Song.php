<?php

namespace App\Entity;

use App\Repository\SongRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SongRepository::class)
 */
class Song
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=1024)
     */
    private $path;

    /**
     * @ORM\Column(type="string", length=1024)
     */
    private $webPath;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $title;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $trackNumber;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $year;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $genre;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $duration;

    /**
     * @ORM\ManyToOne(targetEntity=Album::class, inversedBy="songs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $album;

    /**
     * @ORM\ManyToOne(targetEntity=Artist::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $artist;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getWebPath(): ?string
    {
        return $this->webPath;
    }

    public function setWebPath(string $webPath): self
    {
        $this->webPath = $webPath;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTrackNumber(): ?int
    {
        return $this->trackNumber;
    }

    public function setTrackNumber(?int $trackNumber): self
    {
        $this->trackNumber = $trackNumber;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): self
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

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(string $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): self
    {
        $this->album = $album;

        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): self
    {
        $this->artist = $artist;

        return $this;
    }
}
