<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Field\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\PersistentCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldConfiguratorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmbedField;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

/**
 * @author Lukas Lücke <lukas@luecke.me>
 */
final class EmbedConfigurator implements FieldConfiguratorInterface
{
    private $typeExtractor;
    private $entityManager;

    public function __construct(PropertyTypeExtractorInterface $typeExtractor, EntityManagerInterface $entityManager)
    {
        $this->typeExtractor = $typeExtractor;
        $this->entityManager = $entityManager;
    }

    public function supports(FieldDto $field, EntityDto $entityDto): bool
    {
        return EmbedField::class === $field->getFieldFqcn();
    }

    public function configure(FieldDto $field, EntityDto $entityDto, AdminContext $context): void
    {
        $propertyName = $field->getProperty();
        if (!$entityDto->isAssociation($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" field is not a Doctrine association, so it cannot be used as an embed field.', $propertyName));
        }

        $pathParts = explode('.', $propertyName);
        $targetClass = $entityDto->getFqcn();
        if (count($pathParts) > 1) {
            $parentPath = implode('.', array_slice($pathParts, 0, -1));
            $property = array_slice($pathParts, -1, 1)[0];

            $parentType = $this->typeExtractor->getTypes($entityDto->getFqcn(), $parentPath)[0];
            $classMetadata = $this->entityManager->getClassMetadata($parentType->getClassName());

            $targetEntityFqcn = $classMetadata->getAssociationTargetClass($property);
            $mappedBy = $classMetadata->getAssociationMappedByTargetField($property);
        } else {
            $targetEntityFqcn = $field->getDoctrineMetadata()->get('targetEntity');
            $mappedBy = $field->getDoctrineMetadata()->get('mappedBy');
        }

        $field->setCustomOption('mappedBy', $mappedBy);

        // the target CRUD controller can be NULL; in that case, field value doesn't link to the related entity
        $targetCrudControllerFqcn = $field->getCustomOption(EmbedField::OPTION_CRUD_CONTROLLER)
            ?? $context->getCrudControllers()->findCrudFqcnByEntityFqcn($targetEntityFqcn);
        $field->setCustomOption(EmbedField::OPTION_CRUD_CONTROLLER, $targetCrudControllerFqcn);
    }
}
