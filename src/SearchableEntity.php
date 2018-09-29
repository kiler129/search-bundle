<?php

namespace Algolia\SearchBundle;

use JMS\Serializer\ArrayTransformerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SearchableEntity implements SearchableEntityInterface
{
    protected $indexName;
    protected $entity;
    protected $entityMetadata;
    protected $useSerializerGroups;

    private $id;
    private $normalizer;

    public function __construct($indexName, $entity, $entityMetadata, $normalizer, array $extra = [])
    {
        $this->indexName           = $indexName;
        $this->entity              = $entity;
        $this->entityMetadata      = $entityMetadata;
        $this->normalizer          = $normalizer;
        $this->useSerializerGroups = isset($extra['useSerializerGroup']) && $extra['useSerializerGroup'];

        $this->setId();
    }

    /**
     * @inheritdoc
     */
    public function getIndexName()
    {
        return $this->indexName;
    }

    /**
     * @inheritdoc
     */
    public function getSearchableArray()
    {
        return $this->serializeValue(
            $this->entity,
            [
                'fieldsMapping' => $this->entityMetadata->fieldMappings,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    private function setId()
    {
        $ids = $this->entityMetadata->getIdentifierValues($this->entity);

        if (empty($ids)) {
            throw new Exception('Entity has no primary key');
        }

        if (1 == count($ids)) {
            $id = \reset($ids);
            $this->id = \is_scalar($id) ? (string)$id : $this->serializeValue($id);

            return;
        }

        $objectID = '';
        foreach ($ids as $key => $value) {
            $objectID .= $key . '-' . $value . '__';
        }

        $this->id = rtrim($objectID, '_');
    }

    /**
     * @param object $value
     *
     * @return array|string
     */
    private function serializeValue($value, $context = [])
    {
        if ($this->useSerializerGroups) {
            $context['groups'] = [Searchable::NORMALIZATION_GROUP];
        }

        if ($this->normalizer instanceof NormalizerInterface) {
            return $this->normalizer->normalize($value, Searchable::NORMALIZATION_FORMAT, $context);
        }

        if ($this->normalizer instanceof ArrayTransformerInterface) {
            return $this->normalizer->toArray($value);
        }

        throw new \LogicException(
            \sprintf(
                'Invalid normalizer %s passed to %s, expected %s or %s',
                get_class($this->normalizer),
                __METHOD__,
                NormalizerInterface::class,
                ArrayTransformerInterface::class
            )
        );
    }
}
