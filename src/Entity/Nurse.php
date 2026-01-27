<?php

/**
 * Nurse entity – RMDB Hospital
 *
 * We map the nurse table used by our backend. We align columns with the
 * filess.io database: id, user, name, pw, title, specialty, description,
 * location, availability, image.
 */

namespace App\Entity;

use App\Repository\NurseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NurseRepository::class)]
#[ORM\Table(name: 'nurse')]
class Nurse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** We store the login username. */
    #[ORM\Column(length: 50)]
    private ?string $user = null;

    /** We store the display name. */
    #[ORM\Column(length: 70)]
    private ?string $name = null;

    /** We store the password (plain text in this project). */
    #[ORM\Column(length: 20)]
    private ?string $pw = null;

    /** We store the professional title. */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $title = null;

    /** We store the specialty. */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $specialty = null;

    /** We store the profile description. */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** We store the location. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    /** We store the availability. */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $availability = null;

    /** We store the profile image path or URL. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPw(): ?string
    {
        return $this->pw;
    }

    public function setPw(?string $pw): static
    {
        $this->pw = $pw;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSpecialty(): ?string
    {
        return $this->specialty;
    }

    public function setSpecialty(?string $specialty): static
    {
        $this->specialty = $specialty;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getAvailability(): ?string
    {
        return $this->availability;
    }

    public function setAvailability(?string $availability): static
    {
        $this->availability = $availability;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }
}
