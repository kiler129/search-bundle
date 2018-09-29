<?php

namespace Algolia\SearchBundle;


interface SearchableEntityInterface
{
    public function getIndexName();

    /**
     * @return array Serialized entity fields
     */
    public function getSearchableArray();

    /**
     * @return string|int|array Serialized entity identifier
     */
    public function getId();
}
