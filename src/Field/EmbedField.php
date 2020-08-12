<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @author Lukas Lücke <lukas@luecke.me>
 */
final class EmbedField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_CRUD_CONTROLLER = 'crudControllerFqcn';
    public const OPTION_EMBEDDED_ID = 'embeddedId';
    public const OPTION_MODIFY_ACTIONS = 'modifyActions';

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->hideLabel()
            ->setTemplateName('crud/field/embed')
            ->setCustomOption(self::OPTION_MODIFY_ACTIONS, null);
    }

    public function setCrudController(string $crudControllerFqcn): self
    {
        $this->setCustomOption(self::OPTION_CRUD_CONTROLLER, $crudControllerFqcn);

        return $this;
    }

    public function modifyActions(\Closure $configure): self
    {
        $this->setCustomOption(self::OPTION_MODIFY_ACTIONS, $configure);

        return $this;
    }
}
