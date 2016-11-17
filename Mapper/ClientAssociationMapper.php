<?php

namespace OAuth2\ServerBundle\Mapper;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

/**
 * ClientAssociationMapper
 * Adds associations to user's chosen client class, similar to what Sonata
 * EasyExtendsBundle does.
 *
 * @link https://github.com/sonata-project/SonataEasyExtendsBundle/blob/2.x/Mapper/DoctrineORMMapper.php
 * @link https://github.com/sonata-project/SonataEasyExtendsBundle/blob/2.x/Mapper/DoctrineCollector.php
 */
class ClientAssociationMapper implements EventSubscriber
{
    protected $associations;

    protected $clientClass;


    public function __construct($clientClass) {
        $this->clientClass = $clientClass;

        $this->addManyToOneAssociation('OAuth2\ServerBundle\Entity\AuthorizationCode');
        $this->addManyToOneAssociation('OAuth2\ServerBundle\Entity\AccessToken');
        $this->addManyToOneAssociation('OAuth2\ServerBundle\Entity\RefreshToken');
        $this->addOneToOneAssociation('OAuth2\ServerBundle\Entity\ClientPublicKey');
    }

    /**
     * @return array
     */
    public function getSubscribedEvents() {
        return array(
            'loadClassMetadata'
        );
    }

    /**
     * @param $eventArgs
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs) {
        $metadata = $eventArgs->getClassMetadata();

        // Skip classes we don't care
        if (!array_key_exists($metadata->name, $this->associations)) {
            return;
        }

        try {
            foreach ($this->associations[$metadata->name] as $type => $mappings) {
                foreach ($mappings as $mapping) {

                    // the association is already set, skip the native one
                    if ($metadata->hasAssociation($mapping['fieldName'])) {
                        continue;
                    }

                    call_user_func(array($metadata, $type), $mapping);
                }
            }
        } catch (\ReflectionException $e) {
            throw new \RuntimeException(sprintf('Error with class %s : %s', $metadata->name, $e->getMessage()), 404, $e);
        }
    }

    protected function addManyToOneAssociation($class) {
        $this->addAssociation($class, 'mapManyToOne', array(
            'fieldName'       => 'client',
            'targetEntity'    => $this->clientClass,
            'joinColumns' => array(
                array(
                    'name' => 'client_id',
                    'referencedColumnName' => 'client_id',
                    'onDelete' => 'CASCADE',
                    'onUpdate' => 'CASCADE'
                ),
            )
        ));
    }

    protected function addOneToOneAssociation($class) {
        $this->addAssociation($class, 'mapOneToOne', array(
            'fieldName'       => 'client',
            'targetEntity'    => $this->clientClass,
            'joinColumns' => array(
                array(
                    'name' => 'client_id',
                    'referencedColumnName' => 'client_id',
                    'onDelete' => 'CASCADE',
                    'onUpdate' => 'CASCADE'
                ),
            )
        ));
    }

    /**
     * @param $class
     * @param $type
     * @param  array $options
     * @return void
     */
    protected function addAssociation($class, $type, array $options) {
        if (!isset($this->associations[$class])) {
            $this->associations[$class] = array();
        }

        if (!isset($this->associations[$class][$type])) {
            $this->associations[$class][$type] = array();
        }

        $this->associations[$class][$type][] = $options;
    }
}
