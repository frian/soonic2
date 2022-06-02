<?php

namespace App\Entity;

use App\Repository\RadioRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RadioRepository::class)
 */
class Radio
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private $streamUrl;

    /**
     * @ORM\Column(type="string", length=512, nullable=true)
     */
    private $homepageUrl;

    public function __toString()
    {
        if (is_null($this->name)) {
            return 'NULL';
        }

        return $this->name;
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

    public function getStreamUrl(): ?string
    {
        return $this->streamUrl;
    }

    public function setStreamUrl(string $streamUrl): self
    {
        $this->streamUrl = $streamUrl;

        return $this;
    }

    public function getHomepageUrl(): ?string
    {
        return $this->homepageUrl;
    }

    public function setHomepageUrl(?string $homepageUrl): self
    {
        $this->homepageUrl = $homepageUrl;

        return $this;
    }
}
