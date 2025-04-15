<?php

namespace App\Entity;

use App\Repository\ScriptRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScriptRepository::class)]
class Script
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    /**
     * @var Collection<int, Os>
     */
    #[ORM\ManyToMany(targetEntity: Os::class, inversedBy: 'scripts')]
    private Collection $OS;

    /**
     * @var Collection<int, Logic>
     */
    #[ORM\ManyToMany(targetEntity: Logic::class, inversedBy: 'scripts')]
    private Collection $logic_parts;

    #[ORM\Column]
    private ?bool $published = null;

    #[ORM\ManyToOne(inversedBy: 'scripts')]
    private ?User $creator = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $crdate = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    public function __construct()
    {
        $this->OS = new ArrayCollection();
        $this->logic_parts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Collection<int, Os>
     */
    public function getOS(): Collection
    {
        return $this->OS;
    }

    public function addO(Os $o): static
    {
        if (!$this->OS->contains($o)) {
            $this->OS->add($o);
        }

        return $this;
    }

    public function removeO(Os $o): static
    {
        $this->OS->removeElement($o);

        return $this;
    }

    /**
     * @return Collection<int, Logic>
     */
    public function getLogicParts(): Collection
    {
        return $this->logic_parts;
    }

    public function addLogicPart(Logic $logicPart): static
    {
        if (!$this->logic_parts->contains($logicPart)) {
            $this->logic_parts->add($logicPart);
        }

        return $this;
    }

    public function removeLogicPart(Logic $logicPart): static
    {
        $this->logic_parts->removeElement($logicPart);

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCrdate(): ?\DateTimeImmutable
    {
        return $this->crdate;
    }

    public function setCrdate(\DateTimeImmutable $crdate): static
    {
        $this->crdate = $crdate;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
