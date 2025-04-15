<?php

namespace App\Entity;

use App\Repository\LogicRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogicRepository::class)]
class Logic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'logics')]
    #[ORM\JoinColumn(nullable: false)]
    private ?LogicType $type = null;

    /**
     * @var Collection<int, OS>
     */
    #[ORM\ManyToMany(targetEntity: OS::class, inversedBy: 'logics')]
    private Collection $OS;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'dependencies')]
    private Collection $dependencies;

    #[ORM\Column]
    private ?bool $published = null;

    #[ORM\ManyToOne(inversedBy: 'logics')]
    private ?User $creator = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $crdate = null;

    /**
     * @var Collection<int, Script>
     */
    #[ORM\ManyToMany(targetEntity: Script::class, mappedBy: 'logic_parts')]
    private Collection $scripts;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    public function __construct()
    {
        $this->OS = new ArrayCollection();
        $this->dependencies = new ArrayCollection();
        $this->scripts = new ArrayCollection();
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

    public function getType(): ?LogicType
    {
        return $this->type;
    }

    public function setType(?LogicType $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, OS>
     */
    public function getOS(): Collection
    {
        return $this->OS;
    }

    public function addO(OS $o): static
    {
        if (!$this->OS->contains($o)) {
            $this->OS->add($o);
        }

        return $this;
    }

    public function removeO(OS $o): static
    {
        $this->OS->removeElement($o);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getDependencies(): Collection
    {
        return $this->dependencies;
    }

    public function addDependency(self $dependency): static
    {
        if (!$this->dependencies->contains($dependency)) {
            $this->dependencies->add($dependency);
        }

        return $this;
    }

    public function removeDependency(self $dependency): static
    {
        $this->dependencies->removeElement($dependency);

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

    /**
     * @return Collection<int, Script>
     */
    public function getScripts(): Collection
    {
        return $this->scripts;
    }

    public function addScript(Script $script): static
    {
        if (!$this->scripts->contains($script)) {
            $this->scripts->add($script);
            $script->addLogicPart($this);
        }

        return $this;
    }

    public function removeScript(Script $script): static
    {
        if ($this->scripts->removeElement($script)) {
            $script->removeLogicPart($this);
        }

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
