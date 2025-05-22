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
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\ManyToOne]
    private ?PackageManager $package_manager = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class)]
    private Collection $dependencies;

    #[ORM\Column]
    private ?bool $published = null;

    #[ORM\ManyToOne]
    private ?User $creator = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $crdate = null;

    #[ORM\ManyToOne(inversedBy: 'logics')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $modified = null;

    public function __construct()
    {
        $this->dependencies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getname(): ?string
    {
        return $this->name;
    }

    public function setname(string $name): static
    {
        $this->name = $name;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getPackageManager(): ?PackageManager
    {
        return $this->package_manager;
    }

    public function setPackageManager(?PackageManager $package_manager): static
    {
        $this->package_manager = $package_manager;

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getModified(): ?\DateTime
    {
        return $this->modified;
    }

    public function setModified(?\DateTime $modified): static
    {
        $this->modified = $modified;

        return $this;
    }
}
