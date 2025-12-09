<?php

declare(strict_types=1);

namespace LmcTest\Rbac\Role\Doctrine\Asset;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Laminas\Permissions\Rbac\RoleInterface;
use Override;

#[ORM\Entity]
#[ORM\Table(name: 'roles')]
final class Role implements RoleInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 32, unique: true)]
    private ?string $name;

    #[ORM\JoinTable(name: 'role_children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'child_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: self::class, cascade: ['persist'])]
    private Collection $children;

    #[ORM\ManyToMany(targetEntity: Permission::class, cascade: ['persist'], fetch: 'EAGER', indexBy: 'name')]
    private Collection $permissions;

    /**
     * Init the Doctrine collection
     */
    public function __construct(string $name)
    {
        $this->name        = $name;
        $this->children    = new ArrayCollection();
        $this->permissions = new ArrayCollection();
    }

    /**
     * Get the role identifier
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Add a permission
     */
    #[Override]
    public function addPermission(string $name): void
    {
        $permission = new Permission($name);

        $this->permissions[(string) $permission] = $permission;
    }

    #[Override]
    public function getName(): string
    {
        return $this->name ?? '';
    }

    #[Override]
    public function hasPermission(string $name): bool
    {
        return isset($this->permissions[$name]);
    }

    #[Override]
    public function addChild(RoleInterface $child): void
    {
        $this->children[] = $child;
    }

    #[Override]
    public function getChildren(): iterable
    {
        return $this->children->getValues();
    }

    #[Override]
    public function addParent(RoleInterface $parent): void
    {
        // TODO: Implement addParent() method.
    }

    #[Override]
    public function getParents(): iterable
    {
        return [];
    }
}
