# Rezonant's MapperBundle

[![Build Status](https://travis-ci.org/rezonant/RezonantMapperBundle.svg?branch=master)](https://travis-ci.org/rezonant/RezonantMapperBundle)

NOTE: This library is NOT ready for general use! This is sooo pre-alpha!

This Symfony2 bundle provides a flexible mechanism for object-to-object mapping (and back).
Note that this bundle does not cover constructing a Model object from request data nor does it handle creating response text
from a model, as both of these should be the responsibility of your [de]serializer, we recommend JMS Serializer for that.

Maps can be provided automatically using annotations, manually via configuration, or via custom map providers.
The caller can also explicitly specify a map object to be used. The bundle has a built-in caching mechanism which can use
one of a number of built in (or custom) caching strategies. Built in cache strategies include an in-memory store, a 
strategy based on a Doctrine-Commons Cache object, and a mostly useless implementation within a Symfony session bag.

Also, while we don't explicitly test for this use case you can probably use the library to do free declarative 
mapping between anonymous (stdclass) objects. There is rather substantial support for dealing with associative arrays when 
they are part of object-to-object maps so mapping to/from them should work, but your mileage may vary if our tests 
aren't covering your usage.

# Introduction: Use this within a RESTful service

While fairly generic in it's implementation, this bundle is primarily designed to be used within RESTful services written
using Symfony2. Standard Symfony2+FOSRest+JMS projects will usually procure model objects from requests automatically via 
the FOSRest body listener combined with the JMS serializer. One might then use the Symfony validator to assert that the user's
data is valid before continuing. 

On the other end of things, FOSRestControllers which use the response listener 
feature will typically return model objects (instead of Symfony HTTP Kernel Response objects), which will be automatically
serialized using JMS. One might then use the "Accepts" or extension-based formatting hints that FOSRest comes built in with
to select an output format based on what the API consumer wants.

The model object which you (de)serialize to/from should be of a class coupled to your RESTful API, not your entity layer. 
One reason for this is that you may have to support multiple versions of your API. How then should you handle converting 
from your RESTful model into an underlying persistence object? 

RezonantMapperBundle provides a simple but powerful way to accomplish object-to-object mapping. The built-in annotation-based
mapper allows you to declaratively specify the destinations for fields on the source object. Note that destination-driven
annotations are not available, but they could be easily added in by writing a new variant of AnnotationMapProvider.

The decision to focus on source-driven annotations is influenced by the primary use case of this library: to map 
model (service-layer) data onto entity (persistence-layer) data and back again. Since there could be many model classes which
map to the same entity within a single version of an API, and perhaps many versions of an API, it makes sense to 
stick to source-driven annotations because it enforces the same dependency graph that your services already follow: 
Your API layer depends on your persistence layer, but your persistence layer does not depend on your API layer.
That being said, you are free to graft support onto the library if you require it.

# Installation

NOTE: This is still a pre-alpha bundle. Since there are no released versions, you must depend on dev-master (the bleeding
edge). We make absolutely no guarantees about API stability, but we hope this will change soon. Once versions are tagged,
strict semantic versioning will be followed.

    $ composer require rezonant/mapper-bundle=dev-master

You must also depend on the bundle within app/AppKernel.php:

    $bundles = array(
        ...,
        new Rezonant\MapperBundle\RezonantMapperBundle()
    )

If you fail to do so, you will be able to use the bundle's classes, but none of the Symfony DI services will be 
available, and your configurations will be ignored.

# Using the Mapper service

The simplest way to call Mapper is by allowing a map provider to do the heavy lifting:

    $dest = $mapper->map($sourceInstance, "ABC\MyDestinationClass");

Above, the map is found by consulting all registered map providers for a valid mapping between the source and 
destination classes. If one is found, it is used. If one isn't found, an exception is thrown 
(Rezonant\MapperBundle\Exceptions\UnableToMapException).

You can also explicitly provide the map using the third parameter of map(). You can use this along with the 
MapBuilder API to produce arbitrary mappings.

    $map = new MapBuilder()
           ->field(new Reference("fromField"), new Reference("toField"))
           ->build();
    $dest = $mapper->map($sourceInstance, "ABC\MyDestinationClass", $map);

# Annotation-based Mapping

The bundle supports source-driven annotations for specifying field-to-field mappings:

     use Rezonant\MapperBundle\Annotations as Mapper;

     class MyModel {
         /**
          * @Mapper\MapTo("toField")
          */
         public $fromField;
     }

For a deep model structure, you must be sure to specify types using the @Mapper\Type() annotation.
If you are also using JMS serializer, you can use the @JMS\Type() annotation instead and MapperBundle
will respect that. The map generated between two classes will automatically handle all subclasses.

In the above example, the field $fromField will map onto the destination $toField.

# Configuration-based Mapping

In your config.yml (or whereever using Symfony config resources):

     rezonant_mapper:
         maps:
             - source: ABC\MySourceClass
               destination: ABC\MyDestinationClass
               fields:
                   - from: fromField
                     to: toField

You can also specify submaps below each field, for when the source value is an object, and the 
destination value is a different kind of object:

     rezonant_mapper:
         maps:
             - source: ABC\MySourceClass
               destination: ABC\MyDestinationClass
               fields:
                   - from: fromField
                     to: toField
                     map: 
                         fields:
                             - from: deeperFromField
                               to: deeperToField

You can also use deep references:

     rezonant_mapper:
         maps:
             - source: ABC\MySourceClass
               destination: ABC\MyDestinationClass
               fields:
                   - from: fromField
                     to: someField.someOtherField.toField
                     map: 
                         fields:
                             - from: anotherField.deeperFromField
                               to: deeperToField

Note that the types involved in each field reference are auto-detected using PHP reflection if possible.
You can also specify these explicitly:

     rezonant_mapper:
         maps:
             - source: ABC\MySourceClass
               destination: ABC\MyDestinationClass
               fields:
                   - from:
                         name: fromField
                         type: \TypeOfThisField
                     to:
                         name: someField.someOtherField.toField
                         types: [\TypeOfSomeField, \TypeOfSomeOtherField, \TypeOfToField]

This may be required if such information cannot be detected from reflection and/or annotations, or if you
do not want to incur that overhead when the map is first constructed (but don't forget about the caching system)

# Usage in Symfony2

Once installed you can depend on the ```@rezonant.mapper``` service to obtain a configured instance of the Mapper service.

## Configuration

The rezonant_mapper config section can be used for much more than just declaring maps.

### Enabling/Disabling Map Providers

You can control which map providers are used. The following sample shows the default values:

     rezonant_mapper:
         providers:
             annotations:
                 enabled: true
             config: 
                 enabled: true
             custom: []

The "custom" field is an array of classes implementing MapProviderInterface which should be used as map providers.

### Caching

The included cache layer can be controlled by manipulating the "caching" section of the configuration. The 
following shows the default values:

     rezonant_mapper:
         caching:
             enabled: false
             strategy: 'Rezonant\MapperBundle\Cache\Strategies\MemoryCacheStrategy'

The "enabled" flag will enable/disable the caching engine. The "strategy" field lets you specify a strategy class 
to be used. You can implement your own strategies by implementing Rezonant\MapperBundle\Cache\CacheStrategyInterface
or use one of the built-in ones found within the Rezonant\MapperBundle\Cache\Strategies namespace. Of particular note
is the DoctrineCacheStrategy, which will allow you to wrap any Doctrine-Commons Cache object. Doctrine-Commons comes with
a number of cache providers including APC and Memcache.

# Usage outside of Symfony2

Coming soon! This package is a Symfony2 dependency injection bundle for Symfony2 app kernels, and as such is built to 
be used with Symfony's dependency injector. Its functionality is not yet available in a non-Symfony manner, but 
that will likely change in the future. In the mean time, even if you are not using Symfony DI, you can still pull 
this project using Composer and construct a Mapper service manually using the Rezonant\MapperBundle\Mapper class. 
Should you construct your own instance, you should provide a MapProvider instance (we recommend AnnotationMapper, 
you will need to depend on Doctrine Commons and provide an AnnotationReader). If you intend to always pass 
explicit maps you can pass NULL for the map provider. 

	 use Rezonant\MapperBundle\Mapper;
	 use Rezonant\MapperBundle\Providers\AnnotationMapProvider;
	 use Doctrine\Common\Annotations\AnnotationReader;

     $mapper = new Mapper(new AnnotationMapProvider(new AnnotationReader()));

You could also use Rezonant\MapperBundle\Providers\ConfigMapProvider to inject a number of premade maps into the Mapper
service. Simply construct your maps (manually or using Rezonant\MapperBundle\MapBuilder) and pass them as an array into
a new ConfigMapProvider, then use that for your Mapper instance.

## Caching

Caching can be used without using the Symfony configuration layer by constructing a CacheProvider and passing your
MapProvider of choice to it along with your chosen CacheStrategy implementation. You can moderate between 
several MapProviders by using the Rezonant\MapperBundle\Providers\MapProviderModerator class. This is how the 
Symfony bundle internally constructs the MapProvider for the Mapper service based on the rezonant_mapper configuration
section.

# Testing

To run the tests you (currently) must construct a Symfony project and symlink the sources into it:
   
    $ cd mapper-bundle
    $ symfony new symfony2
	$ ln -s ../../src/Rezonant symfony2/src/Rezonant
    $ phpunit
    
