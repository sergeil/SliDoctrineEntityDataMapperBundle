# SliDoctrineEntityDataMapperBundle [![Build Status](https://travis-ci.org/sergeil/SliDoctrineEntityDataMapperBundle.svg?branch=develop)](https://travis-ci.org/sergeil/SliDoctrineEntityDataMapperBundle)

Bundle provides tools that simplify mapping data coming from client-side onto Doctrine ORM managed entities.

Features:
 * All basic scalar types mapping (boolean, numbers ...)
 * All types of associations are supported (+bidirectional aspect of relations is taken care of out of the box)
 * Complex data types transformation and mapping - when a value before being mapped onto an entity is transformed
 * Additional DI services injection to a setter method when a value is mapped
 * Flexible mapping of date/datetimes

## Installation

Add this dependency to your composer.json:

    "sergeil/doctrine-entity-data-mapper-bundle": "dev-develop"

Update your AppKernel class and add this:

    new Sli\DoctrineEntityDataMapperBundle\SliDoctrineEntityDataMapperBundle(),

## Documentation

TODO

## Licensing

This bundle is under the MIT license. See the complete license in the bundle:
Resources/meta/LICENSE