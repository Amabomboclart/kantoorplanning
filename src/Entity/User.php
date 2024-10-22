<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=255)
     */
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private ?string $name = null;


    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private ?string $firstName = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private ?string $mail = null;

    /**
     * @ORM\Column(type="integer", length=255, nullable=true)
     */
    private ?int $monday = null;

    /**
     * @ORM\Column(type="integer", length=255, nullable=true)
     */
    private ?int $tuesday = null;

    /**
     * @ORM\Column(type="integer", length=255, nullable=true)
     */
    private ?int $wednesday = null;

    /**
     * @ORM\Column(type="integer", length=255, nullable=true)
     */
    private ?int $thursday = null;

    /**
     * @ORM\Column(type="integer", length=255, nullable=true)
     */
    private ?int $friday = null;

    public function getMonday(): ?string
    {
        return $this->monday;
    }

    public function setMonday(?int $monday): self
    {
        $this->monday = $monday;

        return $this;
    }

    public function getTuesday(): ?int
    {
        return $this->tuesday;
    }

    public function setTuesday(?int $tuesday): self
    {
        $this->tuesday = $tuesday;

        return $this;
    }

    public function getWednesday(): ?int
    {
        return $this->wednesday;
    }

    public function setWednesday(?int $wednesday): self
    {
        $this->wednesday = $wednesday;

        return $this;
    }

    public function getThursday(): ?int
    {
        return $this->thursday;
    }

    public function setThursday(?int $thursday): self
    {
        $this->thursday = $thursday;

        return $this;
    }

    public function getFriday(): ?int
    {
        return $this->friday;
    }

    public function setFriday(?int $friday): self
    {
        $this->friday = $friday;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $Mail): self
    {
        $this->name = $Mail;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $FirstName): self
    {
        $this->firstName = $FirstName;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $Mail): self
    {
        $this->mail = $Mail;

        return $this;
    }
}
